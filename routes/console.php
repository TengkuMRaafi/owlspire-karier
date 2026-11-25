<?php

use Illuminate\Support\Facades\Schedule;

// Papan Top 5 karier di landing: kumpulkan permintaan pasar tiap Minggu pukul 00:00 (zona waktu di config/career_trend.php).
Schedule::command('karier:kumpulkan-tren')
    ->weeklyOn(0, '0:00')
    ->timezone(config('career_trend.timezone', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Pengingat belajar: cek tiap 5 menit, kirim sekali per hari pada jam pilihan pengguna (bila hari itu belum belajar).
Schedule::command('study:remind')->everyFiveMinutes()->withoutOverlapping(10);

// Alert lowongan: ringkasan pagi ke email atau Telegram.
Schedule::command('jobs:alerts')->dailyAt('08:00')->timezone(config('platform.study.timezone', 'Asia/Jakarta'))->withoutOverlapping(120);

// Tagihan yang lewat batas waktu bayar (60 menit untuk Midtrans) ditutup otomatis; QR tidak berlaku lagi.
Schedule::command('orders:expire')->everyFiveMinutes()->withoutOverlapping(10);

// Kiriman grup Telegram: cek tiap jam tepat; grup menerima info lowongan pukul 06.00 dan 18.00 menurut zona waktunya (WIB/WITA/WIT).
Schedule::command('telegram:digest')->hourly()->withoutOverlapping(55);
