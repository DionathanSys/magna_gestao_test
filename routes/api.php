<?php

use App\Http\Controllers\Api\WebScraperViagemController;
use App\Http\Middleware\VerifyWebScraperSignature;
use Illuminate\Support\Facades\Route;

Route::post('/integracoes/viagens', WebScraperViagemController::class)
    ->middleware(VerifyWebScraperSignature::class)
    ->name('api.integracoes.viagens');
