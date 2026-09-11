<?php

use AlphaPay\Laravel\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => config('alphapay.webhook_middleware', ['api']),
], function () {
    Route::post(
        config('alphapay.webhook_path', 'webhooks/alphapay'),
        [WebhookController::class, 'handle']
    )->name('alphapay.webhook');
});
