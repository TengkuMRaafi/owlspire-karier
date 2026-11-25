<?php

namespace App\Jobs;

use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Satu email broadcast untuk satu penerima. Dicoba ulang 3 kali bila SMTP sedang bermasalah. */
class SendBroadcastEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public int $broadcastId, public int $userId)
    {
    }

    public function handle(): void
    {
        $broadcast = EmailBroadcast::find($this->broadcastId);
        if (! $broadcast) {
            return;
        }

        $user = User::find($this->userId);
        // Berhenti berlangganan atau akun berubah sejak antre: lewati, jangan dihitung sebagai gagal.
        if (! $user || $user->broadcast_opt_out_at || ! $user->email_verified_at || $user->suspended_at) {
            $broadcast->skipOne();

            return;
        }

        Mail::to($user->email)->send(new BroadcastMail(
            $broadcast,
            BroadcastMail::firstName($user->name),
            BroadcastMail::unsubscribeUrlFor($user),
        ));

        $broadcast->recordResult(true);
    }

    public function failed(Throwable $e): void
    {
        Log::warning("Broadcast #{$this->broadcastId} ke pengguna #{$this->userId} gagal: ".$e->getMessage());
        EmailBroadcast::find($this->broadcastId)?->recordResult(false);
    }
}
