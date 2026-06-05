# Plano de Implementação — Recebimento de Anexos via IMAP Hostinger no Laravel

## Objetivo

Implementar no sistema Laravel uma automação para:

1. Conectar em uma conta de e-mail da Hostinger via IMAP.
2. Ler e-mails recebidos.
3. Filtrar e-mails válidos por remetente, assunto ou regras internas.
4. Baixar os anexos.
5. Salvar os arquivos na VPS em `storage/app/private`.
6. Registrar os e-mails e anexos no banco de dados.
7. Montar um novo e-mail com esses anexos.
8. Enviar o novo e-mail usando o serviço atual de envio, preferencialmente Resend.

---

## Decisão técnica

Usar:

- Laravel 12
- Filas/Jobs
- Scheduler do Laravel
- Conta Hostinger via IMAP
- Biblioteca `webklex/laravel-imap`
- Armazenamento privado em `storage/app/private`
- Envio por `Mail` usando Resend já configurado no projeto

Referências úteis:

- Webklex Laravel IMAP: https://github.com/Webklex/laravel-imap
- Documentação Laravel Scheduler: https://laravel.com/docs/scheduling
- Documentação Laravel Queues: https://laravel.com/docs/queues
- Documentação Laravel Mail: https://laravel.com/docs/mail

---

## Fluxo esperado

```text
Conta Microsoft corporativa
    ↓ encaminhamento automático
Conta Hostinger: entrada@seudominio.com.br
    ↓ IMAP
Laravel Scheduler
    ↓
ReadIncomingMailboxJob
    ↓
Baixa anexos
    ↓
Salva em storage/app/private/mail/incoming
    ↓
Registra no banco
    ↓
Dispatch SendForwardedAttachmentsJob
    ↓
Envia novo e-mail com anexos
```

---

## Configuração da conta de e-mail

Criar uma conta exclusiva para automação, por exemplo:

```text
entrada@seudominio.com.br
```

Configurações IMAP Hostinger:

```env
IMAP_HOST=imap.hostinger.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_USERNAME=entrada@seudominio.com.br
IMAP_PASSWORD=senha_da_conta
IMAP_DEFAULT_FOLDER=INBOX
```

Configurações de processamento:

```env
MAIL_INCOMING_ALLOWED_SENDERS=
MAIL_INCOMING_FORWARD_TO=destino@empresa.com.br
MAIL_INCOMING_STORAGE_DISK=local
MAIL_INCOMING_STORAGE_PATH=private/mail/incoming
MAIL_INCOMING_MAX_ATTACHMENT_MB=20
MAIL_INCOMING_MARK_AS_SEEN=true
```

Observação:

- `MAIL_INCOMING_ALLOWED_SENDERS` pode iniciar vazio.
- Depois pode aceitar uma lista separada por vírgula.
- Exemplo:

```env
MAIL_INCOMING_ALLOWED_SENDERS=cliente1@email.com,cliente2@email.com
```

---

## Instalação do pacote IMAP

Executar:

```bash
composer require webklex/laravel-imap
```

Publicar configuração:

```bash
php artisan vendor:publish --tag=imap-config
```

Validar se as extensões PHP necessárias estão instaladas.

No Ubuntu, verificar:

```bash
php -m | grep -E "imap|mbstring"
```

Se faltar IMAP:

```bash
sudo apt update
sudo apt install php-imap php-mbstring -y
sudo systemctl restart apache2
sudo systemctl restart php8.3-fpm
```

Ajustar o restart conforme o servidor estiver usando Apache mod_php ou PHP-FPM.

---

## Estrutura de banco de dados

Criar migration:

```bash
php artisan make:model IncomingEmail -m
php artisan make:model IncomingEmailAttachment -m
```

### Tabela `incoming_emails`

Campos sugeridos:

```php
Schema::create('incoming_emails', function (Blueprint $table) {
    $table->id();

    $table->string('message_id')->unique();
    $table->string('from_email')->nullable();
    $table->string('from_name')->nullable();
    $table->string('subject')->nullable();
    $table->timestamp('received_at')->nullable();

    $table->string('status')->default('pending');
    // pending, processed, ignored, failed

    $table->json('raw_headers')->nullable();
    $table->json('metadata')->nullable();

    $table->text('error_message')->nullable();

    $table->timestamps();
});
```

### Tabela `incoming_email_attachments`

Campos sugeridos:

```php
Schema::create('incoming_email_attachments', function (Blueprint $table) {
    $table->id();

    $table->foreignId('incoming_email_id')
        ->constrained('incoming_emails')
        ->cascadeOnDelete();

    $table->string('original_filename');
    $table->string('stored_filename');
    $table->string('mime_type')->nullable();
    $table->unsignedBigInteger('size_bytes')->nullable();

    $table->string('disk')->default('local');
    $table->string('path');

    $table->string('status')->default('stored');
    // stored, attached_to_outgoing_email, failed

    $table->json('metadata')->nullable();

    $table->timestamps();
});
```

Rodar:

```bash
php artisan migrate
```

---

## Models

### `IncomingEmail`

Relacionamento:

```php
public function attachments()
{
    return $this->hasMany(IncomingEmailAttachment::class);
}
```

### `IncomingEmailAttachment`

Relacionamento:

```php
public function email()
{
    return $this->belongsTo(IncomingEmail::class, 'incoming_email_id');
}
```

---

## Estrutura de classes sugerida

Criar:

```text
app/
├── Actions/
│   └── Mail/
│       ├── StoreIncomingEmailAction.php
│       ├── StoreIncomingAttachmentAction.php
│       └── ShouldProcessIncomingEmailAction.php
│
├── Jobs/
│   └── Mail/
│       ├── ReadIncomingMailboxJob.php
│       └── SendForwardedAttachmentsJob.php
│
├── Mail/
│   └── ForwardIncomingAttachmentsMail.php
│
└── Services/
    └── Mail/
        └── IncomingMailboxService.php
```

---

## Job 1 — Ler caixa de entrada

Criar:

```bash
php artisan make:job Mail/ReadIncomingMailboxJob
```

Responsabilidade:

- Conectar no IMAP.
- Buscar e-mails não lidos ou não processados.
- Verificar `message_id`.
- Ignorar se já existe no banco.
- Validar se deve processar.
- Salvar e-mail.
- Salvar anexos.
- Disparar job de envio.

Pseudocódigo:

```php
public function handle(IncomingMailboxService $service): void
{
    $messages = $service->getUnprocessedMessages();

    foreach ($messages as $message) {
        try {
            if ($service->alreadyProcessed($message)) {
                continue;
            }

            if (! $service->shouldProcess($message)) {
                $service->registerIgnored($message);
                continue;
            }

            $incomingEmail = $service->storeEmail($message);

            $service->storeAttachments($incomingEmail, $message);

            SendForwardedAttachmentsJob::dispatch($incomingEmail->id)
                ->onQueue('mail-send');

            $service->markAsSeen($message);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
```

---

## Serviço — `IncomingMailboxService`

Responsabilidades:

1. Criar conexão IMAP.
2. Buscar mensagens.
3. Extrair remetente.
4. Extrair assunto.
5. Extrair data.
6. Extrair anexos.
7. Validar remetente permitido.
8. Salvar arquivos.
9. Marcar e-mail como lido.

Métodos sugeridos:

```php
class IncomingMailboxService
{
    public function getUnprocessedMessages(): iterable;
    public function alreadyProcessed($message): bool;
    public function shouldProcess($message): bool;
    public function storeEmail($message): IncomingEmail;
    public function storeAttachments(IncomingEmail $incomingEmail, $message): void;
    public function markAsSeen($message): void;
}
```

---

## Salvamento dos anexos

Salvar sempre em disco privado.

Exemplo de estrutura:

```text
storage/app/private/mail/incoming/2026/06/04/{incoming_email_id}/arquivo.pdf
storage/app/private/mail/incoming/2026/06/04/{incoming_email_id}/arquivo.xml
```

Regra de nome:

```text
uuid_nome-original-sanitizado.ext
```

Exemplo:

```text
8f3b10af-5d38-42ff-bb81-danfe-123.pdf
```

Cuidados:

- Sanitizar nome original.
- Não confiar em extensão.
- Registrar MIME type.
- Limitar tamanho.
- Permitir apenas tipos esperados, se fizer sentido.

Tipos recomendados inicialmente:

```php
[
    'application/pdf',
    'application/xml',
    'text/xml',
    'application/zip',
    'image/jpeg',
    'image/png',
]
```

---

## Job 2 — Enviar novo e-mail com anexos

Criar:

```bash
php artisan make:job Mail/SendForwardedAttachmentsJob
```

Responsabilidade:

- Buscar o `IncomingEmail`.
- Carregar anexos.
- Montar e-mail.
- Anexar arquivos.
- Enviar para destinatário configurado.
- Atualizar status.

Pseudocódigo:

```php
public function handle(): void
{
    $incomingEmail = IncomingEmail::with('attachments')->findOrFail($this->incomingEmailId);

    Mail::to(config('mail-incoming.forward_to'))
        ->send(new ForwardIncomingAttachmentsMail($incomingEmail));

    $incomingEmail->update([
        'status' => 'processed',
    ]);
}
```

---

## Mailable

Criar:

```bash
php artisan make:mail ForwardIncomingAttachmentsMail
```

Responsabilidade:

- Montar assunto.
- Criar corpo simples.
- Anexar arquivos salvos.

Assunto sugerido:

```text
Anexos recebidos - {remetente} - {assunto original}
```

Corpo sugerido:

```text
E-mail recebido automaticamente.

Remetente: {from_name} <{from_email}>
Assunto original: {subject}
Data de recebimento: {received_at}

Anexos processados:
- arquivo1.pdf
- arquivo2.xml
```

No mailable, anexar usando caminhos reais do storage:

```php
public function attachments(): array
{
    return $this->incomingEmail->attachments
        ->map(function ($attachment) {
            return Attachment::fromPath(storage_path('app/' . $attachment->path))
                ->as($attachment->original_filename)
                ->withMime($attachment->mime_type);
        })
        ->all();
}
```

---

## Config própria

Criar arquivo:

```text
config/mail-incoming.php
```

Conteúdo sugerido:

```php
return [
    'imap' => [
        'host' => env('IMAP_HOST', 'imap.hostinger.com'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'default_folder' => env('IMAP_DEFAULT_FOLDER', 'INBOX'),
    ],

    'allowed_senders' => array_filter(
        array_map('trim', explode(',', env('MAIL_INCOMING_ALLOWED_SENDERS', '')))
    ),

    'forward_to' => env('MAIL_INCOMING_FORWARD_TO'),

    'storage' => [
        'disk' => env('MAIL_INCOMING_STORAGE_DISK', 'local'),
        'path' => env('MAIL_INCOMING_STORAGE_PATH', 'private/mail/incoming'),
        'max_attachment_mb' => (int) env('MAIL_INCOMING_MAX_ATTACHMENT_MB', 20),
    ],

    'mark_as_seen' => env('MAIL_INCOMING_MARK_AS_SEEN', true),
];
```

---

## Scheduler

No Laravel 12, configurar em `routes/console.php` ou no arquivo onde o projeto já concentra o Scheduler.

Exemplo:

```php
use App\Jobs\Mail\ReadIncomingMailboxJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ReadIncomingMailboxJob)
    ->everyMinute()
    ->withoutOverlapping()
    ->onQueue('mail-receive');
```

No servidor, garantir cron do Laravel:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

---

## Filas

Se o projeto usa database queue:

```env
QUEUE_CONNECTION=database
```

Criar tabelas, se ainda não existir:

```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

Rodar worker:

```bash
php artisan queue:work --queue=mail-receive,mail-send,default --tries=3 --timeout=120
```

Em produção, configurar Supervisor.

Exemplo conceitual:

```ini
[program:laravel-worker-mail]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/do/projeto/artisan queue:work --queue=mail-receive,mail-send,default --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/caminho/do/projeto/storage/logs/worker-mail.log
stopwaitsecs=3600
```

---

## Proteções obrigatórias

### 1. Não processar e-mail duplicado

Usar `message_id` único.

Se não houver `message_id`, gerar hash com:

```text
from_email + subject + received_at + nomes dos anexos
```

### 2. Não salvar anexo público

Nunca usar:

```text
storage/app/public
```

Usar:

```text
storage/app/private
```

### 3. Limitar tamanho dos anexos

Rejeitar ou ignorar anexos acima de:

```env
MAIL_INCOMING_MAX_ATTACHMENT_MB=20
```

### 4. Registrar falhas

Salvar erro em:

```text
incoming_emails.error_message
incoming_emails.status = failed
```

### 5. Evitar concorrência

Usar:

```php
withoutOverlapping()
```

E, se necessário, lock no Job:

```php
Cache::lock('mail:read-incoming-mailbox', 60)->block(5, function () {
    // processa
});
```

### 6. Não reenviar infinitamente

Se o e-mail de destino também encaminhar para a caixa de entrada, pode criar loop.

Adicionar regra:

- Ignorar remetente igual ao e-mail do sistema.
- Ignorar assunto que comece com `Anexos recebidos -`.
- Ignorar cabeçalho interno, se houver.

---

## Logs recomendados

Usar logs com contexto:

```php
Log::info('Iniciando leitura da caixa IMAP', [
    'account' => config('mail-incoming.imap.username'),
]);

Log::info('E-mail recebido processado', [
    'incoming_email_id' => $incomingEmail->id,
    'message_id' => $incomingEmail->message_id,
    'attachments_count' => $incomingEmail->attachments()->count(),
]);

Log::error('Erro ao processar e-mail recebido', [
    'message_id' => $messageId ?? null,
    'error' => $exception->getMessage(),
]);
```

---

## Telas opcionais no Filament

Criar Resource:

```bash
php artisan make:filament-resource IncomingEmail
```

Tabela:

- ID
- Remetente
- Assunto
- Data recebimento
- Quantidade de anexos
- Status
- Criado em

Actions:

- Ver detalhes
- Reprocessar envio
- Baixar anexo
- Marcar como ignorado

Relation Manager:

```text
IncomingEmailAttachmentRelationManager
```

Campos:

- Nome original
- MIME type
- Tamanho
- Status
- Path
- Criado em

---

## Comando manual para teste

Criar comando:

```bash
php artisan make:command Mail/ReadIncomingMailboxCommand
```

Assinatura:

```php
protected $signature = 'mail:read-incoming {--limit=10}';
```

Uso:

```bash
php artisan mail:read-incoming --limit=5
```

Esse comando deve disparar a leitura de forma síncrona ou chamar o mesmo serviço usado pelo Job.

---

## Critérios de aceite

A implementação será considerada concluída quando:

1. O Laravel conseguir conectar na conta Hostinger via IMAP.
2. O comando manual `php artisan mail:read-incoming --limit=5` funcionar.
3. Um e-mail com PDF/XML enviado para a conta Hostinger for lido.
4. Os anexos forem salvos em `storage/app/private/mail/incoming`.
5. Os dados forem registrados em `incoming_emails` e `incoming_email_attachments`.
6. O mesmo e-mail não for processado duas vezes.
7. O sistema conseguir reenviar os anexos para o destinatário configurado.
8. Falhas ficarem registradas em log e no banco.
9. O Scheduler executar automaticamente a cada minuto.
10. O worker da fila processar os Jobs em produção.

---

## Sequência recomendada de implementação

### Fase 1 — Infra

1. Criar conta Hostinger.
2. Testar login via webmail.
3. Configurar `.env`.
4. Instalar `webklex/laravel-imap`.
5. Publicar config.
6. Criar `config/mail-incoming.php`.

### Fase 2 — Banco

1. Criar models.
2. Criar migrations.
3. Criar relacionamentos.
4. Rodar migrations.

### Fase 3 — Leitura IMAP

1. Criar `IncomingMailboxService`.
2. Implementar conexão.
3. Buscar mensagens não lidas.
4. Extrair dados básicos.
5. Criar comando manual de teste.

### Fase 4 — Anexos

1. Extrair anexos.
2. Validar tamanho.
3. Validar MIME type.
4. Salvar em storage privado.
5. Registrar no banco.

### Fase 5 — Envio

1. Criar Mailable.
2. Criar Job de envio.
3. Anexar arquivos salvos.
4. Enviar via configuração de e-mail atual do Laravel/Resend.
5. Atualizar status para `processed`.

### Fase 6 — Automação

1. Criar `ReadIncomingMailboxJob`.
2. Configurar Scheduler.
3. Configurar queue worker.
4. Configurar Supervisor em produção.

### Fase 7 — Filament opcional

1. Criar Resource `IncomingEmailResource`.
2. Criar Relation Manager de anexos.
3. Adicionar action de reprocessamento.
4. Adicionar action de download.

---

## Observações importantes para o Opencode

- Não colocar senha de e-mail hardcoded.
- Não salvar anexos em pasta pública.
- Não usar `storage:link` para esses anexos.
- Não processar e-mail duplicado.
- Não apagar e-mails da caixa automaticamente no primeiro momento.
- Inicialmente apenas marcar como lido.
- Manter logs detalhados.
- Usar Service + Actions, conforme padrão do projeto.
- Usar nomes de variáveis descritivos.
- Evitar lógica pesada dentro do Controller.
- Preferir Jobs para leitura, envio e reprocessamento.
- Garantir que o sistema funcione manualmente antes de ativar Scheduler.
