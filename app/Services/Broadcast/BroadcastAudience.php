<?php

namespace App\Services\Broadcast;

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Siapa yang boleh menerima broadcast: pengguna biasa dengan email terverifikasi, tidak ditangguhkan,
 * dan belum menekan "Berhenti menerima info". Premium = langganan aktif (sama dengan halaman Pengguna).
 */
class BroadcastAudience
{
    public function query(string $audience): Builder
    {
        $query = User::query()
            ->where('role', 'member')
            ->whereNotNull('email_verified_at')
            ->whereNull('suspended_at')
            ->whereNull('broadcast_opt_out_at');

        $premium = Entitlement::query()->select('user_id')
            ->where('type', 'subscription')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));

        return match ($audience) {
            'premium' => $query->whereIn('id', $premium),
            'free' => $query->whereNotIn('id', $premium),
            default => $query,
        };
    }

    public function count(string $audience): int
    {
        return $this->query($audience)->count();
    }

    /** @return array<string, int> jumlah penerima per pilihan "Kirim ke" */
    public function counts(): array
    {
        return collect(array_keys(\App\Models\EmailBroadcast::AUDIENCES))->mapWithKeys(fn ($a) => [$a => $this->count($a)])->all();
    }
}
