<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailId extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return BrandedMail::make(
            'Verifikasi email '.config('platform.brand.name'),
            'Selamat datang di '.config('platform.brand.name').'!',
            ['Satu langkah lagi: pastikan email ini memang milikmu. Klik tombol di bawah untuk mengaktifkan akunmu.'],
            'Verifikasi email',
            $url,
            ['Link ini berlaku 60 menit. Kalau kamu tidak membuat akun, abaikan saja email ini.'],
        );
    }
}
