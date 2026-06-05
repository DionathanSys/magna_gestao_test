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
    ReadIncomingMailboxJob::dispatch()->onQueue(config('mail-inbound.queue.ingest'));
    $this->info('Job de leitura da caixa foi enfileirado.');
})->purpose('Ler emails recebidos e iniciar ingestão');

Artisan::command('mail:test-imap {--folder=} {--limit=5}', function () {
    $folderName = (string) ($this->option('folder') ?: config('mail-inbound.imap.folder'));
    $limit = max(1, (int) $this->option('limit'));

    $this->info('Testando conexao IMAP...');
    $this->line('Host: ' . config('mail-inbound.imap.host'));
    $this->line('Porta: ' . config('mail-inbound.imap.port'));
    $this->line('Usuario: ' . config('mail-inbound.imap.username'));
    $this->line('Pasta: ' . $folderName);

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

        $folder = $client->getFolder($folderName);

        if (! $folder) {
            $this->error('Pasta IMAP nao encontrada.');

            return self::FAILURE;
        }

        $messages = $folder
            ->query()
            ->all()
            ->limit($limit)
            ->get();

        $this->info('Conexao IMAP estabelecida com sucesso.');
        $this->line('Mensagens retornadas: ' . $messages->count());

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

        return self::FAILURE;
    }
})->purpose('Testar conexao IMAP sem ingerir emails');

Schedule::command('email:diario')->dailyAt('07:00')->runInBackground();
Schedule::command('email:diario')->dailyAt('17:10')->runInBackground();
Schedule::job(new ReadIncomingMailboxJob(), config('mail-inbound.queue.ingest'))
    ->everyMinute()
    ->withoutOverlapping();
