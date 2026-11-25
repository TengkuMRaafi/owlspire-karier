<?php

namespace App\Services\Study;

use App\Models\DailyActivity;
use Illuminate\Support\Carbon;

/** Catatan aktivitas belajar harian dan streak. Hari dihitung di zona waktu config/platform.php > study.timezone. */
class StudyActivity
{
    public static function today(): Carbon
    {
        return Carbon::now(config('platform.study.timezone', 'Asia/Jakarta'))->startOfDay();
    }

    /** Mencatat satu aktivitas ('review' = satu kartu dijawab, 'exam' = satu simulasi selesai). Tidak pernah melempar error. */
    public function record(int $userId, string $kind): void
    {
        try {
            $row = DailyActivity::firstOrCreate(['user_id' => $userId, 'day' => self::today()->toDateString()]);
            $row->increment($kind === 'exam' ? 'exams' : 'reviews');
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{reviews:int, exams:int} aktivitas hari ini */
    public function todayCounts(int $userId): array
    {
        $row = DailyActivity::where('user_id', $userId)->where('day', self::today()->toDateString())->first();

        return ['reviews' => (int) ($row?->reviews ?? 0), 'exams' => (int) ($row?->exams ?? 0)];
    }

    /** @return array{current:int, best:int, active_today:bool, week:array<int,array{date:string,label:string,active:bool,today:bool}>} */
    public function streak(int $userId): array
    {
        $days = DailyActivity::where('user_id', $userId)
            ->where(fn ($q) => $q->where('reviews', '>', 0)->orWhere('exams', '>', 0))
            ->orderByDesc('day')->limit(400)->pluck('day')
            ->map(fn ($d) => substr((string) $d, 0, 10))->all();
        $set = array_flip($days);

        $today = self::today();
        $cursor = isset($set[$today->toDateString()]) ? $today->copy() : $today->copy()->subDay();
        $current = 0;
        while (isset($set[$cursor->toDateString()])) {
            $current++;
            $cursor->subDay();
        }

        // Rangkaian terpanjang dari hari-hari yang tercatat.
        $best = 0;
        $run = 0;
        $prev = null;
        foreach (array_reverse($days) as $d) {
            $run = ($prev !== null && Carbon::parse($prev)->addDay()->toDateString() === $d) ? $run + 1 : 1;
            $best = max($best, $run);
            $prev = $d;
        }

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $week[] = ['date' => $d->toDateString(), 'label' => $d->translatedFormat('D'), 'active' => isset($set[$d->toDateString()]), 'today' => $i === 0];
        }

        return ['current' => $current, 'best' => max($best, $current), 'active_today' => isset($set[$today->toDateString()]), 'week' => $week];
    }
}
