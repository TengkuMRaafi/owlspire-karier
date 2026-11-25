<?php

namespace App\Services\Ai;

use App\Models\AiUsage;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use Carbon\Carbon;

/** Kuota harian alat AI (config/platform.php > ai_tools). Premium, admin, dan staf memakai batas Premium. */
class AiQuota
{
    public function __construct(private EntitlementService $entitlements)
    {
    }

    /** Saat jatah harian terisi lagi: pergantian hari berikutnya (zona waktu aplikasi), sama dengan dasar hitung `day`. */
    public static function resetsAt(): Carbon
    {
        return now()->addDay()->startOfDay();
    }

    public function limit(User $user, string $feature): int
    {
        $key = $this->entitlements->isPremium($user->id) ? 'premium_daily' : 'free_daily';

        return max(0, (int) config("platform.ai_tools.{$feature}.{$key}", 1));
    }

    public function used(User $user, string $feature): int
    {
        return (int) AiUsage::where('user_id', $user->id)->where('feature', $feature)
            ->where('day', now()->toDateString())->value('used');
    }

    public function remaining(User $user, string $feature): int
    {
        return max(0, $this->limit($user, $feature) - $this->used($user, $feature));
    }

    public function consume(User $user, string $feature): void
    {
        $row = AiUsage::firstOrCreate(['user_id' => $user->id, 'feature' => $feature, 'day' => now()->toDateString()], ['used' => 0]);
        $row->increment('used');
    }
}
