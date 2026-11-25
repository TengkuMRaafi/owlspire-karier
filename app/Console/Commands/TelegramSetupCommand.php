<?php

namespace App\Console\Commands;

use App\Services\Telegram\GroupBot;
use App\Services\Telegram\OwlspireBot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetupCommand extends Command
{
    protected $signature = 'telegram:setup {--webhook : Daftarkan webhook juga (sama dengan php artisan telegram:webhook)}';

    protected $description = 'Atur tampilan bot Telegram versi baru: nama OwlSpire.ID, deskripsi, dan daftar perintah (menu /)';

    public function handle(): int
    {
        $token = config('services.telegram.token');
        if (! filled($token)) {
            $this->error('Isi TELEGRAM_BOT_TOKEN di .env dulu.');

            return self::FAILURE;
        }

        $name = mb_substr((string) config('platform.telegram.name', 'OwlSpire.ID'), 0, 64);
        $steps = [
            'Nama bot' => ['setMyName', ['name' => $name]],
            'Deskripsi' => ['setMyDescription', ['description' => mb_substr((string) config('platform.telegram.description'), 0, 512)]],
            'Deskripsi singkat (About)' => ['setMyShortDescription', ['short_description' => mb_substr((string) config('platform.telegram.about'), 0, 120)]],
            'Daftar perintah' => ['setMyCommands', ['commands' => OwlspireBot::commands()]],
            'Perintah di grup' => ['setMyCommands', ['commands' => GroupBot::commands(), 'scope' => ['type' => 'all_group_chats']]],
        ];

        $failed = false;
        foreach ($steps as $label => [$method, $payload]) {
            $res = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
            $ok = $res->successful() && data_get($res->json(), 'ok');
            $failed = $failed || ! $ok;
            $this->line(($ok ? '[ok]    ' : '[gagal] ').$label.($ok ? '' : ': '.data_get($res->json(), 'description', $res->body())));
        }

        if ($this->option('webhook')) {
            $failed = $this->call('telegram:webhook') !== self::SUCCESS || $failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
