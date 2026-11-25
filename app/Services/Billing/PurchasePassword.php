<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

/** Sandi acak untuk akun yang dibuat otomatis saat beli tanpa daftar: dibuat, disimpan terenkripsi di tagihan, dihapus saat sandi diganti */
class PurchasePassword
{
    private const KEY = 'initial_password';

    /** Tanpa huruf/angka yang mirip (0 O 1 l I), supaya mudah diketik bila tidak disalin. Format 4-4-4. */
    public static function generate(): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $all = $letters.$digits;

        do {
            $chars = [];
            for ($i = 0; $i < 12; $i++) {
                $chars[] = $all[random_int(0, strlen($all) - 1)];
            }
            $plain = implode('', $chars);
        } while (! preg_match('/[A-Za-z]/', $plain) || ! preg_match('/\d/', $plain));

        return implode('-', str_split($plain, 4));
    }

    public function remember(Order $order, string $plain): void
    {
        $order->update(['meta' => array_merge($order->meta ?? [], [self::KEY => Crypt::encryptString($plain)])]);
    }

    /** Sandi awal hanya terlihat oleh pemilik akunnya sendiri, selama ia belum membuat sandi sendiri. */
    public function reveal(Order $order, ?User $viewer): ?string
    {
        $enc = $order->meta[self::KEY] ?? null;
        if (! $enc || ! $viewer || $viewer->id !== $order->user_id || ! $viewer->auto_password || $viewer->password_set_at !== null) {
            return null;
        }

        try {
            return Crypt::decryptString($enc);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Dipanggil saat sandi akun berubah: sandi awal tidak berlaku lagi, jadi jangan disimpan. */
    public function forget(User $user): void
    {
        Order::where('user_id', $user->id)->where('meta', 'like', '%"'.self::KEY.'"%')->get()->each(function (Order $order) {
            $meta = $order->meta ?? [];
            unset($meta[self::KEY]);
            $order->update(['meta' => $meta]);
        });
    }
}
