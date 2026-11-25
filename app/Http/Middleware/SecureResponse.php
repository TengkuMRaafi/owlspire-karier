<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Header keamanan dasar, plus larangan cache untuk halaman login/sensitif agar tombol Back tidak membuka halaman lama. */
class SecureResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(self), geolocation=()');
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if (config('security.csp')) {
            $headers->set('Content-Security-Policy', $this->csp());
        }

        // Halaman untuk pengguna masuk, ruang/hasil ujian, tagihan, dan form Masuk/Daftar tidak boleh disimpan cache browser.
        $sensitive = $request->user() || $request->routeIs('exam.*', 'billing.*', 'login', 'register');
        if ($sensitive && ! $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $headers->set('Pragma', 'no-cache');
            $headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * CSP yang realistis untuk aplikasi Blade + Vite ini: skrip dan gaya inline masih diizinkan (halaman memakai keduanya),
     * tetapi object/base/form/frame dikunci, dan sumber luar dibatasi ke yang dipakai (font, YouTube, Turnstile).
     */
    private function csp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-src https://www.youtube-nocookie.com https://challenges.cloudflare.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}