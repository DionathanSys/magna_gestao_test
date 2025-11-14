<?php

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

Schedule::command('email:diario')->dailyAt('17:30');
