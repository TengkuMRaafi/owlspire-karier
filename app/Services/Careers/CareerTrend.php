<?php

namespace App\Services\Careers;

use App\Models\User;
use App\Models\Career;
use App\Models\ExamAttempt;
use App\Models\CareerTrendWeek;
use App\Models\JobListing;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/** Papan karier di landing. */
class CareerTrend
{
    /** @return array{days:int, bucket:int, start:Carbon, next:Carbon} */
    public function window(?CarbonInterface $now = null): array
    {
        $days = max(1, (int) config('platform.trending.refresh_days', 3));
        $now = $now ? Carbon::instance($now) : now();
        $bucket = intdiv($now->getTimestamp(), 86400 * $days);
        $tz = config('app.timezone');
        $start = Carbon::createFromTimestamp($bucket * $days * 86400, $tz);

        return ['days' => $days, 'bucket' => $bucket, 'start' => $start, 'next' => $start->copy()->addDays($days)];
    }

    public function board(?CarbonInterface $now = null): array
    {
        // Tampilan publik: pakai arsip mingguan terbaru bila ada. Dengan $now (tes/pratinjau) dihitung langsung.
        if ($now === null && ($snap = $this->fromSnapshot())) {
            return $snap;
        }

        $w = $this->window($now);
        // Cache hanya menyimpan data polos (angka, string).
        $plain = $now !== null
            ? $this->compute($w)
            : Cache::remember('career-trend:v3:'.$w['days'].':'.$w['bucket'], $w['next'], fn () => $this->compute($w));

        $careers = Career::listed()->get()->keyBy('id');
        $items = collect($plain['rows'])
            ->filter(fn ($r) => $careers->has($r['id']))
            ->map(fn ($r) => ['career' => $careers[$r['id']]] + $r)
            ->values();

        return [
            'items' => $items,
            'live' => $plain['live'],
            'days' => $w['days'],
            'updated_at' => $w['start'],
            'next_at' => $w['next'],
            'totals' => $plain['totals'],
        ];
    }

    /**
     * Simpan papan satu minggu: lowongan dari API ($demand = hasil CareerDemandCollector::collect()['counts'])
     * ditambah simulasi selesai dan karier target dari database. Tampil di landing sampai Minggu berikutnya.
     *
     * @return array<int,array{career:Career,jobs:int,sims:int,targets:int,score:float,position:int}>
     */
    public function rebuild(array $demand, ?CarbonInterface $at = null, bool $save = true): array
    {
        $tz = config('career_trend.timezone', 'Asia/Jakarta');
        $at = ($at ? Carbon::instance($at) : now())->copy()->timezone($tz);
        $weekStart = $at->copy()->startOfDay();
        $weekStart->subDays($weekStart->dayOfWeek); // Minggu = 0

        $careers = Career::listed()->get();
        [$sims, $targets] = $this->platformSignals($at);
        $wJob = (float) config('platform.trending.weight_job', 1);
        $wSim = (float) config('platform.trending.weight_sim', 3);
        $wTarget = (float) config('platform.trending.weight_target', 5);

        $scores = [];
        foreach ($careers as $c) {
            $j = (int) ($demand[$c->id]['jobs'] ?? 0);
            $s = (int) ($sims[$c->exam_type_id] ?? 0);
            $t = (int) ($targets[$c->id] ?? 0);
            $scores[$c->id] = ['jobs' => $j, 'sims' => $s, 'targets' => $t, 'score' => $j * $wJob + $s * $wSim + $t * $wTarget];
        }
        $pos = $this->order($careers, $scores);

        $result = [];
        $rows = [];
        $stamp = now()->toDateTimeString();
        foreach ($careers->sortBy(fn ($c) => $pos[$c->id]) as $c) {
            $sc = $scores[$c->id];
            $result[] = ['career' => $c] + $sc + ['position' => $pos[$c->id]];
            $rows[] = [
                'week_start' => $weekStart->toDateString(), 'career_id' => $c->id,
                'jobs' => $sc['jobs'], 'sims' => $sc['sims'], 'targets' => $sc['targets'], 'score' => $sc['score'],
                'position' => $pos[$c->id], 'per_source' => json_encode($demand[$c->id]['sources'] ?? []),
                'created_at' => $stamp, 'updated_at' => $stamp,
            ];
        }
        if ($save && $rows) {
            CareerTrendWeek::upsert($rows, ['week_start', 'career_id'], ['jobs', 'sims', 'targets', 'score', 'position', 'per_source', 'updated_at']);
        }

        return $result;
    }

    /** Papan dari arsip mingguan terbaru; null bila belum pernah dikumpulkan. */
    private function fromSnapshot(): ?array
    {
        $latest = CareerTrendWeek::query()->max('week_start');
        if (! $latest) {
            return null;
        }
        $latest = Carbon::parse($latest)->toDateString();
        $prevWeek = CareerTrendWeek::query()->where('week_start', '<', $latest)->max('week_start');
        $cur = CareerTrendWeek::query()->where('week_start', $latest)->get()->keyBy('career_id');
        $prev = $prevWeek ? CareerTrendWeek::query()->where('week_start', Carbon::parse($prevWeek)->toDateString())->pluck('position', 'career_id') : collect();

        $careers = Career::listed()->get()->keyBy('id');
        $max = (float) ($cur->max('score') ?: 0);
        $live = $max > 0;
        $tz = config('career_trend.timezone', 'Asia/Jakarta');

        $items = [];
        foreach ($cur->sortBy('position') as $r) {
            if (! $careers->has($r->career_id)) {
                continue;
            }
            $items[] = [
                'career' => $careers[$r->career_id], 'id' => $r->career_id, 'position' => 0,
                'delta' => $prev->has($r->career_id) ? (int) $prev[$r->career_id] - (int) $r->position : 0,
                'heat' => 0, 'jobs' => (int) $r->jobs, 'sims' => (int) $r->sims, 'targets' => (int) $r->targets, 'score' => (float) $r->score,
            ];
        }
        // Karier yang baru ditambahkan setelah arsip dibuat ikut tampil di bawah, nilainya nol, sampai Minggu berikutnya.
        foreach ($careers as $c) {
            if (! $cur->has($c->id)) {
                $items[] = ['career' => $c, 'id' => $c->id, 'position' => 0, 'delta' => 0, 'heat' => 0, 'jobs' => 0, 'sims' => 0, 'targets' => 0, 'score' => 0.0];
            }
        }
        foreach ($items as $i => &$it) {
            $it['position'] = $i + 1;
            $it['heat'] = $live ? max(6, (int) round($it['score'] / $max * 100)) : max(8, 100 - $i * 5);
        }
        unset($it);

        $week = Carbon::parse($latest, $tz);
        $stamp = $cur->sortByDesc('updated_at')->first()->updated_at;

        return [
            'items' => collect($items),
            'live' => $live,
            'days' => 7,
            'updated_at' => $stamp ? Carbon::instance($stamp)->timezone($tz) : $week,
            'next_at' => $week->copy()->addDays(7),
            'totals' => [
                'jobs' => array_sum(array_column($items, 'jobs')),
                'sims' => array_sum(array_column($items, 'sims')),
                'targets' => array_sum(array_column($items, 'targets')),
            ],
        ];
    }

    /** @return array{0:Collection,1:Collection} simulasi selesai per exam_type_id, dan pengguna target per career_id */
    private function platformSignals(Carbon $at): array
    {
        $utc = $at->copy()->utc();
        $atStr = $utc->toDateTimeString();
        $simFrom = $utc->copy()->subDays((int) config('platform.trending.sim_days', 14))->toDateTimeString();

        $sims = ExamAttempt::query()->whereNotNull('finished_at')->whereBetween('finished_at', [$simFrom, $atStr])
            ->selectRaw('exam_type_id, count(*) as c')->groupBy('exam_type_id')->pluck('c', 'exam_type_id');
        $targets = User::query()->whereNotNull('target_career_id')->where('created_at', '<=', $atStr)
            ->selectRaw('target_career_id, count(*) as c')->groupBy('target_career_id')->pluck('c', 'target_career_id');

        return [$sims, $targets];
    }

    /** Hanya tipe skalar dan array, aman untuk disimpan di cache. */
    private function compute(array $w): array
    {
        $careers = Career::listed()->get();
        $cur = $this->scores($careers, $w['start']);
        $prev = $this->scores($careers, $w['start']->copy()->subDays($w['days']));

        $now = $this->order($careers, $cur);
        $before = $this->order($careers, $prev);
        $hadPrev = collect($prev)->sum('score') > 0;
        $max = collect($cur)->max('score') ?: 0;
        $live = $max > 0;

        $rows = [];
        foreach ($careers->sortBy(fn ($c) => $now[$c->id]) as $c) {
            $s = $cur[$c->id];
            $pos = $now[$c->id];
            $rows[] = [
                'id' => $c->id,
                'position' => $pos,
                'delta' => $hadPrev ? $before[$c->id] - $pos : 0,
                'heat' => $live ? max(6, (int) round($s['score'] / $max * 100)) : max(8, 100 - ($pos - 1) * 5),
                'jobs' => $s['jobs'],
                'sims' => $s['sims'],
                'targets' => $s['targets'],
            ];
        }

        return [
            'rows' => $rows,
            'live' => $live,
            'totals' => [
                'jobs' => array_sum(array_column($rows, 'jobs')),
                'sims' => array_sum(array_column($rows, 'sims')),
                'targets' => array_sum(array_column($rows, 'targets')),
            ],
        ];
    }

    /** @return array<int,array{jobs:int,sims:int,targets:int,score:float}> */
    private function scores(Collection $careers, Carbon $at): array
    {
        $wJob = (float) config('platform.trending.weight_job', 1);
        $wSim = (float) config('platform.trending.weight_sim', 3);
        $wTarget = (float) config('platform.trending.weight_target', 5);
        $jobFrom = $at->copy()->subDays((int) config('platform.trending.job_days', 30))->toDateTimeString();
        $simFrom = $at->copy()->subDays((int) config('platform.trending.sim_days', 14))->toDateTimeString();
        $atStr = $at->toDateTimeString();

        $sims = ExamAttempt::query()->whereNotNull('finished_at')->whereBetween('finished_at', [$simFrom, $atStr])
            ->selectRaw('exam_type_id, count(*) as c')->groupBy('exam_type_id')->pluck('c', 'exam_type_id');
        $targets = User::query()->whereNotNull('target_career_id')->where('created_at', '<=', $atStr)
            ->selectRaw('target_career_id, count(*) as c')->groupBy('target_career_id')->pluck('c', 'target_career_id');
        $perDivision = $careers->groupBy('division')->map->count();

        $official = (array) config('platform.trending.job_sources', []);
        $live = fn () => JobListing::query()
            ->whereHas('source', fn ($q) => $q->whereIn('key', $official))
            ->whereRaw('coalesce(posted_at, created_at) between ? and ?', [$jobFrom, $atStr])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $atStr));

        $divisionJobs = $live()->whereNotNull('division')->selectRaw('division, count(*) as c')->groupBy('division')->pluck('c', 'division');

        $out = [];
        foreach ($careers as $c) {
            $titleHits = $live()->where('title', 'like', '%'.$c->name.'%')->count();
            // Lowongan satu divisi dibagi rata ke karier di divisi itu; judul yang cocok persis bernilai lebih.
            $share = ($divisionJobs[$c->division] ?? 0) / max(1, $perDivision[$c->division] ?? 1);
            $jobs = (int) round($titleHits + $share);
            $s = (int) ($sims[$c->exam_type_id] ?? 0);
            $t = (int) ($targets[$c->id] ?? 0);
            $out[$c->id] = ['jobs' => $jobs, 'sims' => $s, 'targets' => $t, 'score' => $titleHits * $wJob * 3 + $share * $wJob + $s * $wSim + $t * $wTarget];
        }

        return $out;
    }

    /** @return array<int,int> career id => posisi (1 = teratas). Seri dipecah oleh rank awal. */
    private function order(Collection $careers, array $scores): array
    {
        $sorted = $careers->sort(fn ($a, $b) => [$scores[$b->id]['score'], $a->rank, $a->id] <=> [$scores[$a->id]['score'], $b->rank, $b->id])->values();
        $pos = [];
        foreach ($sorted as $i => $c) {
            $pos[$c->id] = $i + 1;
        }

        return $pos;
    }
}
