<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menahan menu yang belum dibuka dan menampilkan halaman "Segera Hadir".
 * Pemakaian di rute: ->middleware(FeatureOpen::class.':learning'). Kuncinya dibaca dari config('platform.{kunci}_open').
 */
class FeatureOpen
{
    /** Teks halaman untuk tiap fitur. */
    private const PAGES = [
        'learning' => [
            'name' => 'Belajar',
            'lead' => 'Jalur belajar terarah dengan video dan sumber terbuka sedang kami rapikan. Sambil menunggu, kenali arah kariermu atau coba simulasi 5 menit.',
        ],
    ];

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (config("platform.{$feature}_open")) {
            return $next($request);
        }

        $page = self::PAGES[$feature] ?? ['name' => ucfirst($feature), 'lead' => 'Fitur ini sedang disiapkan.'];

        return response()->view('coming-soon', $page);
    }
}
