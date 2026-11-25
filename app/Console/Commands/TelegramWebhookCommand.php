<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook {--delete : Hapus webhook}';

    protected $description = 'Daftarkan (atau hapus) webhook bot Telegram ke situs ini. APP_URL harus HTTPS dan bisa diakses publik.';

    public function handle(): int
    {
        $token = config('services.telegram.token');
        $secret = config('services.telegram.webhook_secret');
        if (! filled($token) || (! $this->option('delete') && ! filled($secret))) {
            $this->error('Isi TELEGRAM_BOT_TOKEN dan TELEGRAM_WEBHOOK_SECRET di .env dulu.');

            return self::FAILURE;
        }

        $res = $this->option('delete')
            ? Http::timeout(15)->post("https://api.telegram.org/bot{$token}/deleteWebhook")
            : Http::timeout(15)->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => route('telegram.webhook'), 'secret_token' => $secret, 'allowed_updates' => ['message', 'my_chat_member', 'callback_query'],
            ]);

        $this->line((string) data_get($res->json(), 'description', $res->body()));

        return $res->successful() && data_get($res->json(), 'ok') ? self::SUCCESS : self::FAILURE;
    }
}
