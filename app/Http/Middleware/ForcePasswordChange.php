<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pengguna yang ditandai admin "wajib ganti sandi" hanya bisa membuka halaman ganti sandi (atau keluar). */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->must_change_password && ! $request->routeIs('password.force', 'password.force.update', 'logout')
            && ! $request->session()->has('impersonator_id')) {
            return redirect()->route('password.force');
        }

        return $next($request);
    }
}
