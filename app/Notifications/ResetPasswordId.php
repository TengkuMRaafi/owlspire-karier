<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordId extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return BrandedMail::make(
            'Atur ulang kata sandi '.config('platform.brand.name'),
            'Atur ulang kata sandi',
            ['Kami menerima permintaan untuk mengatur ulang kata sandi akunmu. Klik tombol di bawah untuk membuat kata sandi baru.'],
            'Atur ulang kata sandi',
            $url,
            ["Link ini berlaku {$minutes} menit dan hanya bisa dipakai sekali.", 'Kalau bukan kamu yang meminta, abaikan saja email ini. Kata sandimu tidak berubah.'],
        );
    }
}
