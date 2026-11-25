<?php

namespace App\Jobs;

use App\Models\EmailBroadcast;
use App\Services\Broadcast\BroadcastAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Membagi satu broadcast menjadi satu email per penerima, dengan jeda tipis antar email. */
class ProcessBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public int $broadcastId)
    {
    }

    public function handle(BroadcastAudience $audience): void
    {
        $broadcast = EmailBroadcast::find($this->broadcastId);
        if (! $broadcast || $broadcast->status !== 'queued') {
            return;
        }

        $broadcast->update(['status' => 'sending']);

        $gap = max(0, (int) config('broadcast.delay_seconds', 1));
        $queued = 0;

        $audience->query($broadcast->audience)->select('id')->chunkById(200, function ($users) use ($broadcast, $gap, &$queued) {
            foreach ($users as $user) {
                SendBroadcastEmail::dispatch($broadcast->id, $user->id)->delay(now()->addSeconds($queued * $gap));
                $queued++;
            }
        });

        // Jumlah akhir = yang benar-benar masuk antrean (bisa beda tipis dari hitungan saat tombol ditekan).
        $broadcast->update(['recipients_count' => $queued]);
        $broadcast->finishIfDone();
    }
}
