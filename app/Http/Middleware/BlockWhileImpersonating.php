<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Selama admin masuk sebagai pengguna, aksi yang mengubah kredensial atau menghapus akun diblokir. */
class BlockWhileImpersonating
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->has('impersonator_id')) {
            return back()->with('error', 'Aksi ini dinonaktifkan selama kamu masuk sebagai pengguna lain.');
        }

        return $next($request);
    }
}
