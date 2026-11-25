<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pintu masuk panel: hanya akun internal (admin dan staf). Area tertentu dijaga lagi oleh EnsureArea. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isInternal(), 403, 'Halaman ini khusus admin dan staf.');

        return $next($request);
    }
}
