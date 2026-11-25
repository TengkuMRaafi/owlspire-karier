<?php

namespace App\Services\Account;

use Illuminate\Support\Facades\Route;

/** Membagi fitur Gratis dan Premium (config/platform.php > access) sesuai paket pengguna. */
class PlanAccess
{
    /** @return array{free: array, premium: array} tiap item: icon, label, desc, url, locked */
    public function features(bool $premium): array
    {
        $build = fn (string $tier) => collect(config("platform.access.{$tier}", []))
            ->filter(fn (array $f) => Route::has($f['route']))
            ->map(fn (array $f) => $f + [
                'locked' => $tier === 'premium' && ! $premium,
                'url' => $tier === 'premium' && ! $premium ? route('account.subscription') : route($f['route']),
            ])->values()->all();

        return ['free' => $build('free'), 'premium' => $build('premium')];
    }
}
