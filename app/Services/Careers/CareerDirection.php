<?php

namespace App\Services\Careers;

use App\Models\Career;
use Illuminate\Support\Collection;

/** Menilai jawaban "Cari arah kariermu" dan soal coba simulasi di server (kunci tidak dikirim ke browser). */
class CareerDirection
{
    /** Pertanyaan tanpa bobot, aman dikirim ke browser. */
    public function questions(): array
    {
        return collect(config('arah-karier.questions'))->map(fn (array $q) => [
            'topic' => $q['topic'],
            'text' => $q['text'],
            'options' => collect($q['options'])->pluck(0)->all(),
        ])->all();
    }

    public function trialQuestions(): array
    {
        return collect(config('arah-karier.trial'))->map(fn (array $q) => ['text' => $q['text'], 'options' => $q['options']])->all();
    }

    public function questionCount(): int
    {
        return count(config('arah-karier.questions'));
    }

    public function trialCount(): int
    {
        return count(config('arah-karier.trial'));
    }

    /**
     * @param  array<int,int>  $answers  indeks pilihan per pertanyaan
     * @return array hasil lengkap untuk semua pengguna
     */
    public function analyze(array $answers): array
    {
        $dims = config('arah-karier.dimensions');
        $questions = config('arah-karier.questions');

        // Skor mentah per dimensi dan batas maksimum yang mungkin dicapai.
        $raw = array_fill_keys(array_keys($dims), 0);
        $max = $raw;
        foreach ($questions as $i => $q) {
            foreach (array_keys($dims) as $d) {
                $max[$d] += collect($q['options'])->max(fn ($o) => $o[1][$d] ?? 0);
            }
            foreach ($questions[$i]['options'][$answers[$i]][1] as $d => $w) {
                $raw[$d] += $w;
            }
        }
        $share = collect($raw)->map(fn ($v, $d) => $max[$d] > 0 ? $v / $max[$d] : 0);
        $top = max(0.0001, $share->max());
        $rel = $share->map(fn ($v) => $v / $top)->all();

        $ranked = $this->rank($rel);
        $best = $ranked->first();

        $profile = collect($dims)->map(fn ($m, $d) => [
            'key' => $d, 'label' => $m[0], 'desc' => $m[1], 'tip' => $m[2], 'pct' => (int) round($rel[$d] * 100),
        ])->sortByDesc('pct')->values();

        $result = [
            'answered' => count($answers),
            'top' => $this->card($best),
            'strong' => $profile->take(2)->pluck('label')->all(),
            'profile' => $profile->all(),
            'strengths' => $profile->take(2)->map(fn ($p) => ['label' => $p['label'], 'desc' => $p['desc']])->values()->all(),
        ];

        $result['careers'] = $ranked->take(3)->map(fn ($r) => $this->detail($r, $rel, $answers))->values()->all();
        $result['contrast'] = $this->contrast($ranked->last(), $rel);
        $result['insight'] = $this->insight($profile);

        return $result;
    }

    /** @return array{total:int, correct:int, items:array, areas:array} */
    public function grade(array $answers): array
    {
        $items = collect(config('arah-karier.trial'))->map(function (array $q, int $i) use ($answers) {
            $picked = $answers[$i] ?? null;

            return [
                'text' => $q['text'], 'picked' => $picked === null ? null : $q['options'][$picked],
                'right' => $q['options'][$q['answer']], 'ok' => $picked === $q['answer'], 'explain' => $q['explain'],
                'area' => $q['area'] ?? null,
            ];
        });

        // Skor per bidang untuk radar (urutan mengikuti config trial_areas).
        $areas = collect(config('arah-karier.trial_areas'))->map(function (string $label, string $key) use ($items) {
            $in = $items->where('area', $key);
            $total = $in->count();
            $correct = $in->where('ok', true)->count();

            return ['key' => $key, 'label' => $label, 'correct' => $correct, 'total' => $total, 'pct' => $total ? (int) round($correct / $total * 100) : 0];
        })->values()->all();

        return [
            'total' => $items->count(), 'correct' => $items->where('ok', true)->count(),
            'items' => $items->map(fn ($it) => collect($it)->except('area')->all())->all(), 'areas' => $areas,
        ];
    }

    /** Urutan karier menurut kecocokan relatif terhadap profil tuntutannya. */
    private function rank(array $rel): Collection
    {
        return Career::listed()->with('skills')->get()->map(function (Career $c) use ($rel) {
            $w = $this->weights($c);
            $score = array_sum(array_map(fn ($d, $x) => ($rel[$d] ?? 0) * $x, array_keys($w), $w)) / max(0.0001, array_sum($w));

            return ['career' => $c, 'weights' => $w, 'score' => $score, 'pct' => max(15, min(99, (int) round($score * 100)))];
        })->sortByDesc('score')->values();
    }

    /** Bobot dimensi karier: dari config; karier baru memakai tag (tag pertama 1, kedua 0,6). */
    private function weights(Career $c): array
    {
        $known = array_keys(config('arah-karier.dimensions'));
        if ($p = config("arah-karier.profiles.{$c->slug}")) {
            return $p;
        }
        $tags = array_values(array_intersect((array) $c->detail('tags', []), $known));

        return collect($tags)->mapWithKeys(fn ($t, $i) => [$t => $i === 0 ? 1 : 0.6])->all() ?: ['analitis' => 1];
    }

    private function card(array $r): array
    {
        $c = $r['career'];

        return ['slug' => $c->slug, 'name' => $c->name, 'division' => $c->division, 'summary' => $c->summary, 'pct' => $r['pct'], 'url' => route('careers.show', $c)];
    }

    private function detail(array $r, array $rel, array $answers): array
    {
        $dims = config('arah-karier.dimensions');
        $c = $r['career'];
        $w = collect($r['weights'])->sortDesc();
        $main = $w->filter(fn ($x) => $x >= 0.5)->keys();

        $reasons = [];
        $gaps = [];
        foreach ($main as $d) {
            $pct = (int) round($rel[$d] * 100);
            $line = "{$dims[$d][0]}: peran ini menuntut kemampuan untuk ".mb_strtolower($dims[$d][1]).". Kecenderunganmu di sini {$pct}%";
            if ($rel[$d] >= 0.6) {
                $reasons[] = $line.', jadi ini modal yang sudah kamu punya.';
            } else {
                $gaps[] = "{$line}. Untuk menguatkannya, {$dims[$d][2]}.";
            }
        }
        if (! $reasons) {
            $reasons[] = 'Kecocokannya datang dari gabungan kecenderungan yang tersebar, bukan satu kekuatan yang menonjol.';
        }

        // Jawaban yang paling menopang karier ini, dipakai sebagai bukti.
        $evidence = collect(config('arah-karier.questions'))->map(function (array $q, int $i) use ($answers, $w) {
            $o = $q['options'][$answers[$i]];
            $fit = 0;
            foreach ($o[1] as $d => $x) {
                $fit += $x * ($w[$d] ?? 0);
            }

            return ['fit' => $fit, 'text' => "Pada “{$q['topic']}”, kamu menunjukkan bahwa kamu suka {$o[2]}."];
        })->filter(fn ($e) => $e['fit'] > 0)->sortByDesc('fit')->take(3)->pluck('text')->values()->all();

        $skills = $c->skills->pluck('name')->take(4)->all();
        $steps = ["Coba simulasi {$c->name} (15 soal gratis) untuk merasakan bidangnya."];
        if ($skills) {
            $steps[] = 'Mulai dari skill: '.implode(', ', $skills).'.';
        }
        if ($gaps) {
            $steps[] = 'Kuatkan dulu sisi yang masih rendah sebelum melamar peran ini.';
        }
        $steps[] = 'Jadikan karier target supaya skor kesiapanmu terlacak.';

        return $this->card($r) + ['reasons' => $reasons, 'evidence' => $evidence, 'gaps' => $gaps, 'skills' => $skills, 'steps' => $steps];
    }

    private function contrast(?array $r, array $rel): ?array
    {
        if (! $r) {
            return null;
        }
        $dims = config('arah-karier.dimensions');
        $d = collect($r['weights'])->sortDesc()->keys()->sortBy(fn ($k) => $rel[$k])->first();
        $pct = (int) round(($rel[$d] ?? 0) * 100);

        return $this->card($r) + ['why' => "Peran ini sangat bergantung pada {$dims[$d][0]}, sedangkan kecenderunganmu di sana baru {$pct}%. Bukan berarti mustahil, hanya butuh usaha lebih besar."];
    }

    private function insight(Collection $profile): string
    {
        [$a, $b] = [$profile[0], $profile[1]];
        $low = $profile->last();

        return "Kecenderungan terkuatmu ada di {$a['label']} dan {$b['label']}, dan yang paling jarang kamu pilih adalah {$low['label']}. Peran yang memadukan dua kekuatan teratas biasanya terasa paling wajar dijalani lama.";
    }
}
