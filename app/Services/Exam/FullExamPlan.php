<?php

namespace App\Services\Exam;

use Illuminate\Support\Collection;

/**
 * Menentukan berapa soal yang diambil dari tiap bagian untuk ujian PENUH.
 *
 *  - Semua jenis ujian (15 karier + CPNS, BUMN, Polri/TNI): total ujian penuh 100 soal (atur lewat EXAM_FULL_MAX_QUESTIONS).
 *  - Jatah dibagi rata antar bagian (3 bagian: 34/33/33), soalnya diacak dari bank.
 *  - Bila bank sebuah bagian kurang dari jatahnya, sisanya digeser ke bagian lain yang masih punya soal,
 *    sehingga total tetap setinggi mungkin (tidak lebih dari total isi bank).
 *  - Mode coba tidak lewat sini: tetap question_count_trial per bagian (5 x 3 = 15 soal).
 */
class FullExamPlan
{
    public static function max(): int
    {
        return max(1, (int) config('platform.exam.full_max_questions', 100));
    }

    /**
     * @param  Collection  $segments  tiap item punya id, question_count_full, dan questions_count (jumlah soal di bank)
     * @return array<int,int> id bagian => jumlah soal yang diambil
     */
    public static function allocate(Collection $segments, ?int $cap = null): array
    {
        $cap ??= self::max();
        $segments = $segments->values();
        $n = $segments->count();
        if ($n === 0) {
            return [];
        }

        // 1) Jatah rata per bagian
        $want = self::split($cap, $n);
        $target = $cap;

        // 2) Batasi dengan isi bank, lalu geser kekurangan ke bagian yang masih punya sisa soal
        $avail = $segments->map(fn ($s) => max(0, (int) ($s->questions_count ?? 0)))->all();
        $alloc = [];
        foreach ($want as $i => $w) {
            $alloc[$i] = min($w, $avail[$i]);
        }
        $left = min($target, array_sum($avail)) - array_sum($alloc);
        while ($left > 0) {
            $moved = false;
            foreach ($alloc as $i => $a) {
                if ($left > 0 && $a < $avail[$i]) {
                    $alloc[$i]++;
                    $left--;
                    $moved = true;
                }
            }
            if (! $moved) {
                break;
            }
        }

        $out = [];
        foreach ($segments as $i => $s) {
            $out[(int) $s->id] = $alloc[$i];
        }

        return $out;
    }

    public static function total(Collection $segments, ?int $cap = null): int
    {
        return (int) array_sum(self::allocate($segments, $cap));
    }

    /** Pembagian rata: 100 jadi 34/33/33. */
    public static function split(int $total, int $parts): array
    {
        $base = intdiv($total, $parts);
        $rest = $total % $parts;

        return array_map(fn ($i) => $base + ($i < $rest ? 1 : 0), range(0, $parts - 1));
    }
}
