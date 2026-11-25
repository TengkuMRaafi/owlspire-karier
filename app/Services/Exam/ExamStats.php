<?php

namespace App\Services\Exam;

use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;

/** Data ringkasan untuk landing page, dashboard pengguna, dan papan peringkat. */
class ExamStats
{
    /** Seleksi resmi aktif (CPNS, BUMN, Polri/TNI) lengkap dengan segmen dan jumlah soal per segmen. */
    public function catalog(): Collection
    {
        return ExamType::query()
            ->where('kind', 'selection')
            ->where('is_active', true)
            ->orderBy('position')->orderBy('id')
            ->with(['segments' => fn ($q) => $q->withCount('questions')])
            ->get();
    }

    /** Simulasi karier aktif, urut sesuai peringkat karier. */
    public function careerCatalog(): Collection
    {
        return ExamType::query()
            ->where('kind', 'career')->where('is_active', true)
            ->orderBy('position')->orderBy('id')
            ->with(['segments' => fn ($q) => $q->withCount('questions')])
            ->get();
    }

    /** @return array{soal:int, jenis:int, selesai:int, pengguna:int} */
    public function publicNumbers(): array
    {
        return [
            'pengguna' => User::count(),
            'soal' => Question::count(),
            'jenis' => ExamType::where('is_active', true)->count(),
            'selesai' => ExamAttempt::whereNotNull('finished_at')->count(),
        ];
    }

    public function forUser(int $userId): array
    {
        $finished = ExamAttempt::where('user_id', $userId)->whereNotNull('finished_at');

        return [
            'selesai' => (clone $finished)->count(),
            'terbaik' => (clone $finished)->where('mode', 'full')->whereNull('retake_of')->max('total_score'),
            'terakhir' => (clone $finished)->with('type')->latest('finished_at')->limit(5)->get(),
            'berjalan' => ExamAttempt::where('user_id', $userId)->whereNull('finished_at')
                ->where('deadline_at', '>', now())->with('type')->latest('started_at')->first(),
        ];
    }

    /** Peringkat mode penuh: skor tertinggi, lalu pengerjaan tercepat. Nama disamarkan. */
    public function leaderboard(ExamType $type, int $limit = 20): Collection
    {
        return ExamAttempt::query()
            ->where('exam_type_id', $type->id)
            ->where('mode', 'full')
            ->whereNull('retake_of') // ulangan dengan soal yang sama tidak ikut peringkat
            ->whereNotNull('finished_at')
            ->whereNotNull('user_id')
            ->with('user:id,name,preferences')
            ->orderByDesc('total_score')
            ->limit(200)
            ->get()
            ->reject(fn ($a) => $a->user && ! $a->user->pref('privacy.leaderboard')) // pengguna yang menyembunyikan diri
            ->sortBy([['total_score', 'desc'], fn ($a, $b) => $a->durationSeconds() <=> $b->durationSeconds()])
            ->unique('user_id') // satu baris terbaik per pengguna
            ->take($limit)
            ->values();
    }

    /**
     * Posisi pengguna di peringkat jenis ujian ini (skor terbaiknya). Null bila percobaan ini tidak ikut peringkat:
     * bukan ujian penuh, ulangan, tamu, atau pengguna menyembunyikan diri.
     */
    public function rankOf(ExamAttempt $attempt): ?int
    {
        if ($attempt->mode !== 'full' || $attempt->retake_of || ! $attempt->user_id || ! $attempt->finished_at) {
            return null;
        }

        $pos = $this->leaderboard($attempt->type, 1000)->search(fn ($a) => $a->user_id === $attempt->user_id);

        return $pos === false ? null : $pos + 1;
    }

    /** Peringkat gabungan semua simulasi karier (ujian penuh): skor tertinggi, lalu tercepat. */
    public function careerLeaderboard(int $limit = 20): Collection
    {
        $ids = ExamType::query()->where('kind', 'career')->where('is_active', true)->pluck('id');

        return ExamAttempt::query()
            ->whereIn('exam_type_id', $ids)
            ->where('mode', 'full')
            ->whereNull('retake_of') // ulangan dengan soal yang sama tidak ikut peringkat
            ->whereNotNull('finished_at')
            ->whereNotNull('user_id')
            ->with('user:id,name,preferences')
            ->orderByDesc('total_score')
            ->limit(200)
            ->get()
            ->reject(fn ($a) => $a->user && ! $a->user->pref('privacy.leaderboard')) // pengguna yang menyembunyikan diri
            ->sortBy([['total_score', 'desc'], fn ($a, $b) => $a->durationSeconds() <=> $b->durationSeconds()])
            ->unique('user_id')
            ->take($limit)
            ->values();
    }

    public static function maskName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $first = $parts[0] ?? 'Peserta';
        $initial = isset($parts[1]) ? ' '.mb_strtoupper(mb_substr($parts[1], 0, 1)).'.' : '';

        return $first.$initial;
    }
}
