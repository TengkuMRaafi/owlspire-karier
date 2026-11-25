<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Verifikasi 2 langkah: siapkan rahasia, konfirmasi dengan satu kode, kode pemulihan sekali pakai. */
class TwoFactor
{
    public const RECOVERY_COUNT = 8;

    public function enabled(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    /** Mulai (atau ulang) pengaturan: rahasia baru, belum aktif sampai dikonfirmasi. */
    public function begin(User $user): string
    {
        $secret = Totp::generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();

        return $secret;
    }

    public function uri(User $user): string
    {
        return Totp::uri((string) $user->two_factor_secret, $user->email, config('platform.brand.name'));
    }

    /** Konfirmasi kode pertama. @return string[]|null kode pemulihan (tampil sekali), null bila kode salah */
    public function confirm(User $user, string $code): ?array
    {
        if (! $user->two_factor_secret || $user->two_factor_confirmed_at || $this->checkTotp($user, $code) === false) {
            return null;
        }

        $plain = collect(range(1, self::RECOVERY_COUNT))->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $plain),
        ])->save();

        return $plain;
    }

    /** Kode pemulihan baru (yang lama hangus). @return string[] */
    public function regenerateRecovery(User $user): array
    {
        $plain = collect(range(1, self::RECOVERY_COUNT))->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();
        $user->forceFill(['two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $plain)])->save();

        return $plain;
    }

    public function disable(User $user): void
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
    }

    /** Saat masuk: terima kode aplikasi atau satu kode pemulihan (dipakai lalu hangus). */
    public function verifyLogin(User $user, string $input): bool
    {
        $input = trim($input);
        if (preg_match('/^[\d\s]{6,7}$/', $input)) {
            return $this->checkTotp($user, $input) !== false;
        }

        $codes = (array) $user->two_factor_recovery_codes;
        foreach ($codes as $i => $hash) {
            if (Hash::check(Str::lower($input), $hash)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    public function recoveryLeft(User $user): int
    {
        return count((array) $user->two_factor_recovery_codes);
    }

    /** @return int|false langkah waktu yang dipakai, false bila salah atau kode yang sama sudah dipakai (anti-ulang). */
    private function checkTotp(User $user, string $code): int|false
    {
        $step = Totp::verify((string) $user->two_factor_secret, $code);
        if ($step === null) {
            return false;
        }
        $key = "totp-used:{$user->id}:{$step}";
        if (Cache::has($key)) {
            return false;
        }
        Cache::put($key, true, 120);

        return $step;
    }
}
