<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// URL jadi /api/webhooks/payment/{gateway}. Tanpa CSRF; tanda tangan diverifikasi di gateway.
Route::post('/webhooks/payment/{gateway}', [WebhookController::class, 'handle'])->name('billing.webhook');

// URL jadi /api/telegram/webhook. Dilindungi secret_token (TELEGRAM_WEBHOOK_SECRET); daftarkan dengan php artisan telegram:webhook.
Route::post('/telegram/webhook', TelegramWebhookController::class)->middleware('throttle:120,1')->name('telegram.webhook');
