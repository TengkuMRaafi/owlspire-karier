<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun admin hanya mengelola web dan datanya. Fitur pengguna (dashboard, target karier, CV,
 * simulasi, pembelian) tidak dipakai admin; jalur itu dialihkan ke panel admin.
 * Pengaturan akun (ganti sandi, verifikasi 2 langkah) tetap boleh dibuka admin.
 */
class AdminBukanMember
{
    private const BLOKIR = [
        'dashboard.index', 'account.*', 'careers.target', 'cv.*', 'exam.start', 'billing.*',
    ];

    private const BOLEH = ['account.settings*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && $request->routeIs(...self::BLOKIR) && ! $request->routeIs(...self::BOLEH)) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Akun admin dipakai untuk mengelola web dan datanya, bukan untuk fitur pengguna.');
        }

        return $next($request);
    }
}
