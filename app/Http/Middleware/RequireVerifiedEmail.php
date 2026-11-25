<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias "verified". Menolak pengguna yang emailnya belum terverifikasi (lewat Google atau link email).
 * Admin, staf, dan akun hasil pembelian otomatis dilewatkan, dan tamu (belum masuk) dibiarkan lewat: rute yang butuh login memakai "auth" sendiri.
 */
class RequireVerifiedEmail
{
    public const MESSAGE = 'Verifikasi emailmu dulu untuk memakai fitur ini. Cek link di emailmu, atau masuk dengan Google.';

    public static function blocks(?User $user): bool
    {
        return $user !== null && ! $user->skipsEmailVerification() && ! $user->hasVerifiedEmail();
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::blocks($request->user())) {
            return $next($request);
        }

        return self::deny($request);
    }

    public static function deny(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 403);
        }

        return redirect()->route('verification.notice')->with('error', self::MESSAGE);
    }
}
