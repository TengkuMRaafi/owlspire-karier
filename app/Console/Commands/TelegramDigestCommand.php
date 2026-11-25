<?php

namespace App\Console\Commands;

use App\Models\TelegramGroup;
use App\Services\Telegram\GroupDigest;
use Illuminate\Console\Command;

class TelegramDigestCommand extends Command
{
    protected $signature = 'telegram:digest';

    protected $description = 'Kirim info lowongan ke grup Telegram yang sedang pukul 06.00 atau 18.00 menurut zona waktunya (WIB, WITA, WIT)';

    public function handle(GroupDigest $digest): int
    {
        set_time_limit(0);
        $sent = 0;

        foreach (TelegramGroup::where('is_active', true)->get() as $g) {
            $now = now($g->timezone());
            if (! in_array($now->hour, TelegramGroup::HOURS, true)) {
                continue;
            }
            $slot = $now->format('Y-m-d-H');
            if ($g->last_slot === $slot) {
                continue;
            }

            // Tandai dulu supaya jalan yang tumpang tindih tidak mengirim dobel.
            $g->update(['last_slot' => $slot]);

            try {
                if ($digest->send($g, $now->hour === 6 ? '☀️ Info lowongan pagi' : '🌙 Info lowongan malam')) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                report($e);
            }
            usleep(300_000); // jaga batas kirim Telegram
        }

        $this->info("Grup diproses, terkirim: {$sent}");

        return self::SUCCESS;
    }
}
