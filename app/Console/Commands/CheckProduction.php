<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/** Pemeriksaan keamanan sebelum go-live: php artisan owlspire:cek-produksi */
class CheckProduction extends Command
{
    protected $signature = 'owlspire:cek-produksi';

    protected $description = 'Periksa pengaturan keamanan dan konfigurasi sebelum aplikasi dipublikasikan';

    public function handle(): int
    {
        $rows = [];
        $add = function (string $level, string $item, string $note) use (&$rows) {
            $rows[] = [['ok' => 'OK', 'warn' => 'PERHATIAN', 'fail' => 'GAGAL'][$level], $item, $note];
        };

        $prod = app()->environment('production');
        $add($prod ? 'ok' : 'fail', 'APP_ENV', $prod ? 'production' : 'Sekarang "'.app()->environment().'". Isi APP_ENV=production di server.');
        $add(! config('app.debug') ? 'ok' : 'fail', 'APP_DEBUG', ! config('app.debug') ? 'false' : 'Harus false: debug membocorkan jejak error dan nilai .env.');
        $add(str_starts_with((string) config('app.url'), 'https://') ? 'ok' : 'warn', 'APP_URL', str_starts_with((string) config('app.url'), 'https://') ? config('app.url') : 'Pakai https:// agar tautan email dan cookie aman.');
        $add(filled(config('app.key')) ? 'ok' : 'fail', 'APP_KEY', filled(config('app.key')) ? 'terisi' : 'Jalankan php artisan key:generate.');

        $mailer = config('mail.default');
        $realMail = ! in_array($mailer, ['log', 'array'], true);
        $add($realMail ? 'ok' : 'fail', 'MAIL_MAILER', $realMail ? $mailer : 'Masih "'.$mailer.'": email lupa sandi dan verifikasi tidak terkirim. Isi SMTP/Resend/Brevo di .env.');

        $add(config('session.secure') ? 'ok' : 'warn', 'SESSION_SECURE_COOKIE', config('session.secure') ? 'true' : 'Isi true bila situs memakai HTTPS.');
        $add(config('session.encrypt') ? 'ok' : 'warn', 'SESSION_ENCRYPT', config('session.encrypt') ? 'true' : 'Disarankan true.');
        $add(config('security.csp') ? 'ok' : 'warn', 'Content-Security-Policy', config('security.csp') ? 'aktif' : 'Nonaktif. Isi SECURITY_CSP=true.');

        $driver = config('platform.payment.driver');
        $add($driver === 'midtrans' ? 'ok' : 'warn', 'PAYMENT_DRIVER', $driver === 'midtrans' ? 'midtrans' : 'Sekarang "'.$driver.'" (pembayaran uji coba). Ganti ke midtrans bila sudah menerima pembayaran.');
        if ($driver === 'midtrans') {
            $add(filled(config('platform.payment.midtrans.server_key')) ? 'ok' : 'fail', 'Kunci server Midtrans', filled(config('platform.payment.midtrans.server_key')) ? 'terisi' : 'Belum diisi.');
            $add(config('platform.payment.midtrans.is_production') ? 'ok' : 'warn', 'Midtrans produksi', config('platform.payment.midtrans.is_production') ? 'ya' : 'Masih mode sandbox.');
        }

        $add(config('database.default') !== 'sqlite' ? 'ok' : 'warn', 'Basis data', config('database.default') !== 'sqlite' ? config('database.default') : 'SQLite cocok untuk uji coba; pakai MySQL/PostgreSQL untuk produksi.');
        $add(filled(config('services.turnstile.site_key')) ? 'ok' : 'warn', 'CAPTCHA (Turnstile)', filled(config('services.turnstile.site_key')) ? 'aktif' : 'Belum aktif. Isi TURNSTILE_SITE_KEY dan TURNSTILE_SECRET_KEY untuk melindungi Masuk dan Daftar.');
        if (filled(config('services.telegram.token')) && blank(config('services.telegram.webhook_secret'))) {
            $add('fail', 'TELEGRAM_WEBHOOK_SECRET', 'Token bot terisi tetapi secret webhook kosong.');
        }

        // Akun admin: sandi bawaan yang umum dan 2 langkah.
        try {
            $weak = User::where('role', 'admin')->get()->filter(fn ($a) => collect(['12345678', 'password', 'admin123', 'rahasia-123'])->contains(fn ($p) => Hash::check($p, $a->password)));
            $add($weak->isEmpty() ? 'ok' : 'fail', 'Sandi admin', $weak->isEmpty() ? 'tidak memakai sandi bawaan yang umum' : 'Admin '.$weak->pluck('email')->implode(', ').' masih memakai sandi lemah/bawaan. Ganti sekarang.');
            $no2fa = User::where('role', 'admin')->whereNull('two_factor_confirmed_at')->count();
            $add($no2fa === 0 ? 'ok' : 'warn', 'Verifikasi 2 langkah admin', $no2fa === 0 ? 'semua admin aktif' : "{$no2fa} admin belum mengaktifkannya. Opsi: SECURITY_REQUIRE_ADMIN_2FA=true.");
        } catch (\Throwable) {
            $add('warn', 'Akun admin', 'Tidak bisa diperiksa (jalankan php artisan migrate dulu).');
        }

        $add(is_writable(storage_path()) ? 'ok' : 'fail', 'Folder storage', is_writable(storage_path()) ? 'bisa ditulis' : 'Tidak bisa ditulis oleh server web.');
        $add(file_exists(public_path('build/manifest.json')) ? 'ok' : 'warn', 'Aset front-end', file_exists(public_path('build/manifest.json')) ? 'sudah di-build' : 'Jalankan npm run build.');

        $this->table(['Status', 'Pemeriksaan', 'Keterangan'], $rows);
        $fails = count(array_filter($rows, fn ($r) => $r[0] === 'GAGAL'));
        $warns = count(array_filter($rows, fn ($r) => $r[0] === 'PERHATIAN'));
        $fails > 0 ? $this->error("{$fails} masalah wajib diperbaiki, {$warns} perhatian.") : $this->info("Tidak ada masalah wajib. {$warns} hal perlu perhatian.");

        return $fails > 0 ? self::FAILURE : self::SUCCESS;
    }
}
