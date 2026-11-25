<?php

namespace App\Services\Notify;

use App\Models\TelegramLink;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Kirim pesan ke pengguna lewat email atau Telegram. Gagal kirim tidak melempar error; hasilnya true/false. */
class Notifier
{
    public function toUser(User $user, string $channel, string $subject, string $text): bool
    {
        return match ($channel) {
            'email' => $this->email($user, $subject, $text),
            'telegram' => $this->telegram(TelegramLink::where('user_id', $user->id)->whereNotNull('chat_id')->value('chat_id'), $subject."\n\n".$text),
            default => false,
        };
    }

    public function email(User $user, string $subject, string $text): bool
    {
        try {
            Mail::raw($text, fn ($m) => $m->to($user->email)->subject($subject));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Email pengingat gagal: '.$e->getMessage());

            return false;
        }
    }

    /**
     * @param array<int, array{0: string, 1: string}> $buttons tombol tautan [label, url]; yang bukan https publik dibuang
     */
    public function telegram(?string $chatId, string $text, array $buttons = [], ?string $parseMode = null): bool
    {
        $token = config('services.telegram.token');
        if (! $chatId || ! filled($token)) {
            return false;
        }

        $payload = ['chat_id' => $chatId, 'text' => mb_substr($text, 0, 3900), 'disable_web_page_preview' => true] + ($parseMode ? ['parse_mode' => $parseMode] : []);
        $keyboard = array_values(array_map(fn ($b) => [['text' => $b[0], 'url' => $b[1]]], array_filter($buttons, fn ($b) => $this->validButtonUrl((string) ($b[1] ?? '')))));

        try {
            $res = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", $keyboard ? $payload + ['reply_markup' => ['inline_keyboard' => $keyboard]] : $payload);
            // Telegram menolak seluruh pesan bila satu tombolnya bermasalah; kirim ulang tanpa tombol.
            if (! $res->successful() && $keyboard) {
                $res = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
            }
            // HTML ditolak (tag rusak): kirim ulang sebagai teks biasa daripada pesannya hilang.
            if (! $res->successful() && $parseMode) {
                unset($payload['parse_mode']);
                $res = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", ['text' => html_entity_decode(strip_tags($payload['text']))] + $payload);
            }

            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram gagal: '.$e->getMessage());

            return false;
        }
    }

    /** Panggil metode Bot API apa saja (getChatMember, answerCallbackQuery, dst). Null bila gagal. */
    public function api(string $method, array $payload = []): ?array
    {
        $token = config('services.telegram.token');
        if (! filled($token)) {
            return null;
        }

        try {
            return Http::timeout(10)->post("https://api.telegram.org/bot{$token}/{$method}", $payload)->json() ?: null;
        } catch (\Throwable $e) {
            Log::warning("Telegram {$method} gagal: ".$e->getMessage());

            return null;
        }
    }

    private function validButtonUrl(string $url): bool
    {
        $p = parse_url($url);
        $host = strtolower((string) ($p['host'] ?? ''));

        return ($p['scheme'] ?? '') === 'https' && $host !== '' && $host !== 'localhost' && ! str_ends_with($host, '.local') && ! filter_var($host, FILTER_VALIDATE_IP);
    }
}
