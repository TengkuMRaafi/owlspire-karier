<?php

use App\Http\Middleware\BlockWhileImpersonating;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureArea;
use App\Http\Middleware\EnsureNotSuspended;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\RequireVerifiedEmail;
use App\Http\Middleware\RequireAdminTwoFactor;
use App\Http\Middleware\SecureResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'staff', RequireAdminTwoFactor::class])->prefix('admin')->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di belakang tunnel (VS Code, ngrok) atau proxy (Cloudflare, CyberPanel), HTTPS diakhiri di proxy sehingga Laravel mengira
        // permintaan ber-http dan membuat tautan CSS/JS ber-http (diblokir browser). Isi TRUSTED_PROXIES=* di .env HANYA saat memakai tunnel,
        // atau daftar IP proxy dipisah koma di server. Kosong = tidak ada proxy yang dipercaya (bawaan, paling aman).
        if (filled($proxies = env('TRUSTED_PROXIES'))) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
        $middleware->alias([
            'admin' => EnsureAdmin::class, 'staff' => EnsureStaff::class, 'area' => EnsureArea::class,
            // Dipakai rute Pengaturan akun (sandi, 2FA, unduh data, hapus akun): diblokir saat admin masuk sebagai pengguna.
            'not.impersonating' => BlockWhileImpersonating::class,
            // Menggantikan "verified" bawaan Laravel: admin dan staf dilewatkan, pengguna lain wajib email terverifikasi.
            'verified' => RequireVerifiedEmail::class,
        ]);
        // Urutan: akun ditangguhkan dikeluarkan dulu, lalu wajib ganti sandi, lalu mode pemeliharaan dari pengaturan admin.
        $middleware->web(append: [
            SecureResponse::class, \App\Http\Middleware\AdminBukanMember::class,
            EnsureNotSuspended::class, ForcePasswordChange::class, MaintenanceMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
