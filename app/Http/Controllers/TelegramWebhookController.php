<?php

namespace App\Http\Controllers;

use App\Services\Telegram\OwlspireBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Webhook bot Telegram OwlSpire.ID. Dilindungi secret_token dari setWebhook; logika bot ada di OwlspireBot. */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, OwlspireBot $bot): JsonResponse
    {
        $secret = (string) config('services.telegram.webhook_secret');
        abort_unless($secret !== '' && hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        // Selalu jawab 200 supaya Telegram tidak mengulang pesan yang sama tanpa henti bila ada error.
        try {
            $bot->handle($request->all());
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }
}
