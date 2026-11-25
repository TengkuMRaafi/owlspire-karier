<?php

namespace App\Http\Middleware;

use App\Services\Billing\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengunci fitur khusus Premium. Dipakai di rute sebagai RequirePremium::class.':kunci', dan bisa dimatikan per fitur
 * lewat config/platform.php > premium_only (env PREMIUM_ONLY_*). Admin dan staf lolos lewat EntitlementService.
 */
class RequirePremium
{
    public const INFO = [
        'plan' => [
            'title' => 'Rencana belajar',
            'lead' => 'Jadwal belajar harian yang menyesuaikan tanggal ujian dan hasil simulasimu.',
            'points' => ['Hitung mundur ke hari ujian dan fase belajar', 'Tugas harian dari bagian yang paling lemah', 'Streak, target harian, dan pengingat lewat email atau Telegram'],
        ],
        'interview' => [
            'title' => 'Wawancara AI',
            'lead' => 'Latihan wawancara dengan pewawancara virtual yang menanggapi jawabanmu.',
            'points' => ['Pewawancara bernama yang menyambung ke isi jawabanmu', 'Skor, saran, dan contoh jawaban yang lebih kuat di akhir sesi', 'Empat jenis wawancara: HR, teknis, BUMN, dan situasional'],
        ],
    ];

    public function __construct(private EntitlementService $entitlements)
    {
    }

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! config("platform.premium_only.{$feature}", false)) {
            return $next($request);
        }

        $user = $request->user();

        // Fitur Premium hanya untuk email yang sudah terbukti milik pemakainya.
        if (RequireVerifiedEmail::blocks($user)) {
            return RequireVerifiedEmail::deny($request);
        }

        if ($user && $this->entitlements->isPremium($user->id)) {
            return $next($request);
        }

        // Halaman: tampilkan penjelasan dan tombol upgrade. Aksi (POST dan sejenisnya): tolak.
        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            return response()->view('account.locked', ['info' => self::INFO[$feature] ?? ['title' => 'Fitur Premium', 'lead' => '', 'points' => []]]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Fitur ini khusus Premium.'], 403)
            : redirect()->route('account.subscription')->with('status', 'Fitur ini khusus Premium.');
    }
}
