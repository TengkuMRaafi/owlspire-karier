<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/** Email ke pemilik akun saat akunnya dipakai masuk dari perangkat yang belum dikenal. */
class NewDeviceLogin extends Notification
{
    public function __construct(public string $device, public ?string $ip, public Carbon $at)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->at->copy()->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB';

        return BrandedMail::make(
            'Login baru ke akun '.config('platform.brand.name'),
            'Halo, '.($notifiable->name ?? '').'!',
            ['Akunmu baru saja dipakai masuk dari perangkat yang belum pernah kami kenal.'],
            'Periksa keamanan akun',
            route('account.settings').'#keamanan',
            ['Kalau itu kamu, tidak perlu melakukan apa pun.', 'Kalau bukan kamu, segera ganti kata sandi dan keluarkan perangkat lain dari Pengaturan > Keamanan.'],
            ['Perangkat' => (string) $this->device, 'Alamat IP' => $this->ip ?: 'tidak diketahui', 'Waktu' => $when],
        );
    }
}
