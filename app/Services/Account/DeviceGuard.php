<?php

namespace App\Services\Account;

use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\NewDeviceLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Pengaman berbagi akun saat login: membatasi jumlah perangkat yang masuk bersamaan dan memberi tahu
 * pemilik bila ada perangkat baru. Gagal di sini tidak boleh menggagalkan login. Admin dan staf dikecualikan.
 */
class DeviceGuard
{
    public const COOKIE = 'device_id';

    public function __construct(private SessionManager $sessions)
    {
    }

    /** Panggil tepat sebelum Auth::login(), supaya token "ingat saya" yang baru ikut terpasang di cookie. */
    public function beforeLogin(Request $request, User $user): void
    {
        if ($user->isInternal()) {
            return;
        }

        try {
            $this->sessions->enforceLimit($user, (int) config('platform.security.max_devices', 2));
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $this->recordDevice($request, $user);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Hash cookie perangkat pada permintaan ini (untuk mengenali "perangkat ini"). */
    public static function currentHash(Request $request): string
    {
        return hash('sha256', (string) $request->cookie(self::COOKIE));
    }

    private function recordDevice(Request $request, User $user): void
    {
        $token = (string) $request->cookie(self::COOKIE);
        if (! preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            $token = Str::random(40);
        }

        $info = $this->sessions->describe((string) $request->userAgent());
        $label = $info['browser'].' di '.$info['os'];
        $hadDevices = UserDevice::where('user_id', $user->id)->exists();

        $device = UserDevice::firstOrNew(['user_id' => $user->id, 'token_hash' => hash('sha256', $token)]);
        $isNew = ! $device->exists;
        $device->fill(['label' => $label, 'ip' => $request->ip(), 'last_seen_at' => now()]);
        if ($isNew) {
            $device->first_seen_at = now();
            // Perangkat pertama yang tercatat tidak perlu diberi tahu; hanya perangkat tambahan.
            if ($hadDevices && config('platform.security.notify_new_device', true)) {
                $device->alerted_at = now();
            }
        }
        $device->save();

        Cookie::queue(cookie(self::COOKIE, $token, 60 * 24 * 365 * 2, '/', null, $request->isSecure(), true, false, 'lax'));

        if ($isNew && $device->alerted_at) {
            $ip = $request->ip();
            $at = now();
            // Kirim email setelah respons terkirim, supaya login tidak menunggu server email.
            app()->terminating(function () use ($user, $label, $ip, $at) {
                try {
                    $user->notify(new NewDeviceLogin($label, $ip, $at));
                } catch (\Throwable $e) {
                    report($e);
                }
            });
        }
    }
}
