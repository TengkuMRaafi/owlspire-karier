<?php

namespace App\Jobs;

use App\Models\TelegramGroup;
use App\Services\Telegram\GroupDigest;
use Illuminate\Foundation\Bus\Dispatchable;

/** Contoh kiriman untuk satu grup (dari /contoh), dijalankan setelah respons webhook terkirim. */
class SendGroupDigest
{
    use Dispatchable;

    public function __construct(public int $groupId)
    {
    }

    public function handle(GroupDigest $digest): void
    {
        $g = TelegramGroup::find($this->groupId);
        if ($g) {
            try {
                $digest->send($g, '📌 Contoh info lowongan', true);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
