<?php

namespace App\Http\Middleware;

use App\Services\Settings\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Mode pemeliharaan dari panel admin: pengunjung biasa melihat halaman 503; admin dan staf tetap bisa masuk dan bekerja. */
class MaintenanceMode
{
    public function __construct(private SiteSettings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $m = $this->settings->maintenance();
        if (! $m['enabled']) {
            return $next($request);
        }

        $user = $request->user();
        if (($user && $user->isInternal()) || $request->routeIs('login', 'login.store', 'login.2fa', 'login.2fa.store', 'logout', 'password.*', 'webhooks.*')
            || $request->is('up', 'api/*', 'webhook/*', 'telegram/*')) {
            return $next($request);
        }

        return response()->view('maintenance', ['message' => $m['message']], 503, ['Retry-After' => 600]);
    }
}
