<?php

namespace App\Services\Careers;

use App\Models\Career;
use App\Models\JobListing;
use App\Models\User;
use App\Models\UserSkill;

/** Data tambahan untuk halaman detail satu karier (statistik, skill, jalur, lowongan, tetangga). */
class CareerDetail
{
    public function __construct(private CareerTheme $theme, private ReadinessScore $readiness)
    {
    }

    /**
     * @param  array<string,mixed>|null  $trend  Baris papan tren untuk karier ini (bila ada).
     * @return array<string,mixed>
     */
    public function for(Career $career, ?User $user, ?array $trend, bool $isTarget): array
    {
        $type = $career->examType;
        $questions = $type ? (int) $type->segments->sum(fn ($s) => min($s->question_count_trial, $s->questions_count ?? $s->question_count_trial)) : 0;
        $minutes = $type ? (int) ($type->trial_duration_seconds / 60) : 0;

        $mine = $user ? UserSkill::where('user_id', $user->id)->pluck('skill_id')->all() : [];
        $skills = $this->skills($career, $mine);
        $steps = $this->steps($career);
        $jobs = $this->jobs($career);

        $segments = $type ? $type->segments->map(fn ($s) => [
            'name' => $s->name,
            'count' => (int) min($s->question_count_trial, $s->questions_count ?? $s->question_count_trial),
        ])->all() : [];

        return [
            'theme' => $this->theme->for($career),
            'trend' => $trend,
            'stats' => [
                'skills' => $career->skills->count(),
                'steps' => count($steps),
                'questions' => $questions,
                'minutes' => $minutes,
                'jobs' => (int) ($trend['jobs'] ?? 0),
            ],
            'segments' => $segments,
            'skills' => $skills,
            'steps' => $steps,
            'jobs' => $jobs,
            'readiness' => $user && $isTarget ? $this->readiness->for($user, $career) : null,
            'related' => $this->related($career, $mine),
        ];
    }

    /** Skill dikelompokkan per kategori (satu kelompok bila semuanya sekategori); `have` = sudah ada di profil pengguna. */
    private function skills(Career $career, array $mine): array
    {
        $groups = $career->skills->groupBy(fn ($s) => $s->category ?: 'Lainnya');
        $have = 0;
        $out = [];
        foreach ($groups as $label => $items) {
            $rows = $items->map(function ($s) use ($mine, &$have) {
                $ok = in_array($s->id, $mine, true);
                $have += $ok ? 1 : 0;

                return ['name' => $s->name, 'have' => $ok];
            })->all();
            $out[] = ['label' => (string) $label, 'items' => $rows];
        }

        return ['groups' => $out, 'have' => $have, 'total' => $career->skills->count(), 'multi' => count($out) > 1];
    }

    /** Langkah jalur belajar; materi kursus dicocokkan berdasarkan urutan. */
    private function steps(Career $career): array
    {
        $lessons = $career->course
            ? $career->course->sections->sortBy('position')->flatMap(fn ($s) => $s->lessons->where('is_approved', true)->sortBy('position'))->values()
            : collect();

        $steps = [];
        foreach ((array) $career->detail('path', []) as $i => $p) {
            $l = $lessons->get($i);
            $steps[] = [
                'title' => (string) ($p[0] ?? ''),
                'desc' => (string) ($p[2] ?? ''),
                'type' => $l?->type,
                'minutes' => $l?->duration_minutes,
            ];
        }

        return $steps;
    }

    /** Lowongan aktif yang paling cocok: judul memuat nama karier, divisi sama, atau skill beririsan. */
    private function jobs(Career $career): array
    {
        $ids = $career->skills->pluck('id')->all();
        $base = JobListing::query()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(function ($q) use ($career, $ids) {
                $q->where('title', 'like', '%'.$career->name.'%')->orWhere('division', $career->division);
                if ($ids) {
                    $q->orWhereHas('skills', fn ($s) => $s->whereIn('skills.id', $ids));
                }
            });

        return [
            'total' => (clone $base)->count(),
            'items' => (clone $base)->withCount(['skills as match_count' => fn ($q) => $q->whereIn('skills.id', $ids ?: [0])])
                ->orderByDesc('match_count')->orderByRaw('coalesce(posted_at, created_at) desc')->limit(4)->get(),
        ];
    }

    /**
     * Karier lain. Bila pengguna punya skill, diurutkan dari skill yang paling banyak cocok
     * (seri dipecah oleh divisi yang sama, lalu urutan awal). Tanpa skill: karier satu divisi.
     *
     * @return array{personal:bool, items:array<int,array<string,mixed>>}
     */
    private function related(Career $career, array $mine): array
    {
        $personal = $mine !== [];
        $rows = Career::listed()->with('skills:id,name')->whereKeyNot($career->id)->get()
            ->map(function ($r) use ($career, $mine, $personal) {
                $ids = $r->skills->pluck('id')->all();
                $have = count(array_intersect($ids, $mine));
                $total = count($ids);

                return [
                    'career' => $r,
                    'theme' => $this->theme->for($r),
                    'same' => $r->division === $career->division,
                    'match' => $personal ? ['have' => $have, 'total' => $total, 'pct' => $total ? (int) round($have / $total * 100) : 0] : null,
                ];
            });

        $sorted = $personal
            ? $rows->sort(fn ($a, $b) => [$b['match']['pct'], $b['same'], $a['career']->rank] <=> [$a['match']['pct'], $a['same'], $b['career']->rank])
            : $rows->filter(fn ($r) => $r['same']);

        return ['personal' => $personal, 'items' => $sorted->take(3)->values()->all()];
    }
}
