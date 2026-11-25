<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Opsional (SECURITY_REQUIRE_ADMIN_2FA=true): admin harus mengaktifkan verifikasi 2 langkah dulu. */
class RequireAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (config('security.require_admin_2fa') && $user?->isAdmin() && ! $user->hasTwoFactor() && ! $request->session()->has('impersonator_id')) {
            return redirect()->to(route('account.settings').'#keamanan')
                ->with('error', 'Aktifkan verifikasi 2 langkah dulu untuk membuka panel admin.');
        }

        return $next($request);
    }
}
