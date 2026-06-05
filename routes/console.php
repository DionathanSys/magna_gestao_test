<?php

use App\Jobs\MailInbound\ReadIncomingMailboxJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

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

Schedule::command('email:diario')->dailyAt('07:00')->runInBackground();
Schedule::command('email:diario')->dailyAt('17:10')->runInBackground();
Schedule::job(new ReadIncomingMailboxJob(), config('mail-inbound.queue.ingest'))
    ->everyMinute()
    ->withoutOverlapping();
