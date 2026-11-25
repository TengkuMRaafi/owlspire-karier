<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use App\Services\Exam\ExamStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Kartu hasil yang bisa dibagikan. Tautan publik hanya ada bila pemilik hasil membuatnya, dan bisa dimatikan. */
class ShareController extends Controller
{
    public function store(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        abort_unless($attempt->isFinished(), 404);

        if (! $attempt->share_token) {
            $attempt->share_token = Str::random(32);
            $attempt->save();
        }

        return response()->json(['url' => route('share.show', $attempt->share_token)]);
    }

    public function destroy(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        $attempt->share_token = null;
        $attempt->save();

        return response()->json(['ok' => true]);
    }

    /** Halaman publik: hanya ringkasan skor dengan nama disamarkan. Soal, jawaban, dan pembahasan tidak ikut. */
    public function show(string $token)
    {
        $attempt = ExamAttempt::with(['type', 'user'])
            ->where('share_token', $token)->whereNotNull('finished_at')->firstOrFail();

        $segments = $attempt->score ?? [];
        $max = array_sum(array_column($segments, 'maks'));

        return view('exam.shared', [
            'attempt' => $attempt,
            'type' => $attempt->type,
            'name' => $attempt->user ? ExamStats::maskName($attempt->user->name) : 'Peserta',
            'pct' => $max > 0 ? min(100, (int) round($attempt->total_score / $max * 100)) : 0,
            'benar' => array_sum(array_column($segments, 'benar')),
            'salah' => array_sum(array_column($segments, 'salah')),
            'kosong' => array_sum(array_column($segments, 'kosong')),
            'isFull' => $attempt->mode === 'full',
        ]);
    }

    private function authorizeAttempt(Request $request, ExamAttempt $attempt): void
    {
        $owned = $attempt->user_id
            ? ($request->user() && $request->user()->id === (int) $attempt->user_id)
            : ($attempt->guest_token && hash_equals($attempt->guest_token, (string) $request->session()->get('exam_guest_token', '')));

        abort_unless($owned, 404);
    }
}
