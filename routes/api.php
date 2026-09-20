<?php

use App\Http\Controllers\Api\AutomationWebhookController;
use App\Http\Middleware\VerifyAutomationWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post('/integrations/automation/v1/webhooks', AutomationWebhookController::class)
    ->middleware(VerifyAutomationWebhookSignature::class)
    ->name('api.integrations.automation.webhooks');
