<?php

use Backstage\Mails\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::withoutMiddleware(VerifyCsrfToken::class)
    ->prefix(config('mails.webhooks.routes.prefix'))
    ->group(function () {
        Route::post('{provider}', WebhookController::class)->name('mails.webhook');
    });
