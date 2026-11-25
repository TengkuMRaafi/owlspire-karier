<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Dipakai sebagai `area:orders`: izin per area dibaca dari config/roles.php, bukan ditulis ulang di rute. */
class EnsureArea
{
    public function handle(Request $request, Closure $next, string $area): Response
    {
        abort_unless($request->user()?->canAccess($area), 403, 'Area ini tidak tersedia untuk perananmu.');

        return $next($request);
    }
}
