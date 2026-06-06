<?php

use App\Jobs\MailInbound\ReadIncomingMailboxJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Webklex\IMAP\Facades\Client as ImapClient;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:email', function () {
    $this->call('email:diario');
    $this->info('Email de teste enviado!');
})->purpose('Testar envio de email diário');

Artisan::command('mail:read-incoming', function () {
    Log::info('Disparando leitura manual de emails recebidos', [
        'queue' => config('mail-inbound.queue.ingest'),
    ]);

    ReadIncomingMailboxJob::dispatch()->onQueue(config('mail-inbound.queue.ingest'));
    $this->info('Job de leitura da caixa foi enfileirado.');
})->purpose('Ler emails recebidos e iniciar ingestão');

Artisan::command('mail:test-imap {--folder=} {--limit=5}', function () {
    $folderName = (string) ($this->option('folder') ?: config('mail-inbound.imap.folder'));
    $limit = max(1, (int) $this->option('limit'));
    $username = (string) config('mail-inbound.imap.username');
    $password = (string) config('mail-inbound.imap.password');

    $this->info('Testando conexao IMAP...');
    $this->line('Host: ' . config('mail-inbound.imap.host'));
    $this->line('Porta: ' . config('mail-inbound.imap.port'));
    $this->line('Usuario: ' . ($username !== '' ? $username : '[NAO CONFIGURADO]'));
    $this->line('Pasta: ' . $folderName);

    Log::info('Iniciando teste de conexao IMAP', [
        'host' => config('mail-inbound.imap.host'),
        'port' => config('mail-inbound.imap.port'),
        'username_configured' => $username !== '',
        'password_configured' => $password !== '',
        'folder' => $folderName,
        'limit' => $limit,
    ]);

    if ($username === '' || $password === '') {
        $this->error('IMAP_USERNAME ou IMAP_PASSWORD nao estao configurados no .env.');

        Log::warning('Teste IMAP abortado por configuracao ausente', [
            'username_configured' => $username !== '',
            'password_configured' => $password !== '',
        ]);

        return self::FAILURE;
    }

    try {
        $client = ImapClient::make([
            'host' => config('mail-inbound.imap.host'),
            'port' => config('mail-inbound.imap.port'),
            'encryption' => config('mail-inbound.imap.encryption'),
            'validate_cert' => config('mail-inbound.imap.validate_cert'),
            'username' => config('mail-inbound.imap.username'),
            'password' => config('mail-inbound.imap.password'),
            'protocol' => config('mail-inbound.imap.protocol'),
        ]);

        $client->connect();

        Log::info('Conexao IMAP estabelecida com sucesso no comando de teste', [
            'folder' => $folderName,
        ]);

        $folder = $client->getFolder($folderName);

        if (! $folder) {
            $this->error('Pasta IMAP nao encontrada.');

            Log::warning('Pasta IMAP nao encontrada no comando de teste', [
                'folder' => $folderName,
            ]);

            return self::FAILURE;
        }

        $messages = $folder
            ->query()
            ->all()
            ->limit($limit)
            ->get();

        $this->info('Conexao IMAP estabelecida com sucesso.');
        $this->line('Mensagens retornadas: ' . $messages->count());

        Log::info('Mensagens listadas no teste IMAP', [
            'folder' => $folderName,
            'count' => $messages->count(),
        ]);

        foreach ($messages as $index => $message) {
            $from = $message->getFrom()->first();

            $this->newLine();
            $this->line('Mensagem #' . ($index + 1));
            $this->line('UID: ' . ($message->getUid() ?? 'N/A'));
            $this->line('Message-ID: ' . ($message->getMessageId() ?? 'N/A'));
            $this->line('Assunto: ' . ($message->getSubject() ?? 'Sem assunto'));
            $this->line('De: ' . (($from?->mail ?? 'N/A')));
            $this->line('Anexos: ' . $message->getAttachments()->count());
        }

        return self::SUCCESS;
    } catch (\Throwable $exception) {
        $this->error('Falha ao testar IMAP: ' . $exception->getMessage());

        Log::error('Falha ao testar conexao IMAP', [
            'folder' => $folderName,
            'error' => $exception->getMessage(),
        ]);

        return self::FAILURE;
    }
})->purpose('Testar conexao IMAP sem ingerir emails');

Schedule::command('email:diario')->dailyAt('07:00')->runInBackground();
Schedule::command('email:diario')->dailyAt('17:10')->runInBackground();
Schedule::job(new ReadIncomingMailboxJob(), config('mail-inbound.queue.ingest'))
    ->everyFifteenMinutes()
    ->withoutOverlapping();
