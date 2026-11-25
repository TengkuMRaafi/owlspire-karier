<?php

namespace App\Services\Careers;

/**
 * Tata letak CV hasil bacaan browser (pdf.js): tiap halaman berisi baris teks dengan kotak pembatas (pecahan 0..1 dari halaman).
 * Dipakai untuk dua hal: (1) mengurutkan teks per kolom, supaya CV dua kolom terbaca benar (judul bagian ikut isinya),
 * (2) menunjukkan di mana tiap bagian CV berada di gambar halaman, untuk panah di pratinjau.
 */
class CvLayout
{
    public const MAX_PAGES = 3;

    private const MAX_LINES = 500;

    public function __construct(private CvInsights $insights)
    {
    }

    /**
     * Bersihkan kiriman dari browser. Isinya tidak dipercaya: semua angka dijepit, teks dipotong, bentuk dipaksa.
     *
     * @return array{total:int,pages:array<int,array{lines:array<int,array{t:string,x:float,y:float,w:float,h:float}>}>}|null
     */
    public function sanitize(mixed $raw): ?array
    {
        if (is_string($raw)) {
            $raw = strlen($raw) <= 600000 ? json_decode($raw, true) : null;
        }
        if (! is_array($raw) || ! is_array($raw['pages'] ?? null)) {
            return null;
        }

        $pages = [];
        $total = 0;
        foreach (array_slice(array_values($raw['pages']), 0, self::MAX_PAGES) as $pg) {
            $lines = [];
            foreach (array_slice(array_values((array) ($pg['lines'] ?? [])), 0, self::MAX_LINES) as $ln) {
                if (! is_array($ln) || ! is_string($ln['t'] ?? null)) {
                    continue;
                }
                $t = trim((string) preg_replace('/\s+/u', ' ', $ln['t']));
                if ($t === '' || ! isset($ln['x'], $ln['y'], $ln['w'], $ln['h']) || ! is_numeric($ln['x']) || ! is_numeric($ln['y']) || ! is_numeric($ln['w']) || ! is_numeric($ln['h'])) {
                    continue;
                }
                $lines[] = [
                    't' => mb_substr($t, 0, 300),
                    'x' => round(min(1.0, max(0.0, (float) $ln['x'])), 4),
                    'y' => round(min(1.0, max(0.0, (float) $ln['y'])), 4),
                    'w' => round(min(1.0, max(0.001, (float) $ln['w'])), 4),
                    'h' => round(min(0.2, max(0.002, (float) $ln['h'])), 4),
                ];
            }
            $pages[] = ['lines' => $lines];
            $total += count($lines);
        }

        if ($pages === [] || $total < 3) {
            return null;
        }

        return ['total' => max(count($pages), (int) ($raw['total'] ?? count($pages))), 'pages' => $pages];
    }

    /**
     * Pecah halaman menjadi segmen: tiap segmen = satu judul bagian beserta isinya di kolom yang sama.
     * Baris sebelum judul pertama sebuah kolom jadi segmen tanpa kunci (kepala: nama, kontak).
     *
     * @return array<int,array{key:?string,col:int,items:array<int,array{p:int,l:array}>}>
     */
    public function segments(array $pages): array
    {
        $segments = [];
        $open = [0 => null, 1 => null];

        foreach (array_values($pages) as $pi => $pg) {
            $lines = $pg['lines'] ?? [];
            $split = $this->split($lines);
            $cols = [0 => [], 1 => []];
            foreach ($lines as $ln) {
                $cols[$split !== null && $ln['x'] >= $split - 0.004 ? 1 : 0][] = $ln;
            }

            foreach ($cols as $c => $list) {
                usort($list, fn ($a, $b) => [round($a['y'] / 0.004), $a['x']] <=> [round($b['y'] / 0.004), $b['x']]);
                foreach ($list as $ln) {
                    $key = $this->insights->sectionKey($ln['t']);
                    if ($key !== null) {
                        $segments[] = ['key' => $key, 'col' => $c, 'items' => [['p' => $pi, 'l' => $ln]]];
                        $open[$c] = array_key_last($segments);

                        continue;
                    }
                    if ($open[$c] === null) {
                        $segments[] = ['key' => null, 'col' => $c, 'items' => []];
                        $open[$c] = array_key_last($segments);
                    }
                    $segments[$open[$c]]['items'][] = ['p' => $pi, 'l' => $ln];
                }
            }
        }

        return $segments;
    }

    /**
     * Teks CV dalam urutan baca: kepala (nama, kontak) dulu, lalu tiap bagian menurut letak judulnya di halaman.
     * Kolom yang memuat tulisan terbesar (biasanya nama) didahulukan.
     */
    public function text(array $segments): string
    {
        $heads = array_values(array_filter($segments, fn ($s) => $s['key'] === null && $s['items'] !== []));
        usort($heads, fn ($a, $b) => $this->maxHeight($b) <=> $this->maxHeight($a));
        $sections = array_values(array_filter($segments, fn ($s) => $s['key'] !== null));
        usort($sections, fn ($a, $b) => [$a['items'][0]['p'], round($a['items'][0]['l']['y'] / 0.02), $a['col']] <=> [$b['items'][0]['p'], round($b['items'][0]['l']['y'] / 0.02), $b['col']]);

        $out = [];
        foreach (array_merge($heads, $sections) as $seg) {
            foreach ($seg['items'] as $it) {
                $out[] = $it['l']['t'];
            }
        }

        return implode("\n", $out);
    }

    /**
     * Kotak tiap bagian di halaman (untuk sorotan dan panah). Kunci null = kepala (nama dan kontak).
     *
     * @return array<int,array{p:int,x:float,y:float,w:float,h:float}> diurutkan dari atas
     */
    public function rects(array $segments, ?string $key): array
    {
        $rects = [];
        foreach ($segments as $seg) {
            if ($seg['key'] !== $key || $seg['items'] === []) {
                continue;
            }
            $byPage = [];
            foreach ($seg['items'] as $it) {
                $byPage[$it['p']][] = $it['l'];
            }
            foreach ($byPage as $p => $lines) {
                $x1 = min(array_map(fn ($l) => $l['x'], $lines));
                $y1 = min(array_map(fn ($l) => $l['y'], $lines));
                $x2 = max(array_map(fn ($l) => $l['x'] + $l['w'], $lines));
                $y2 = max(array_map(fn ($l) => $l['y'] + $l['h'], $lines));
                $pad = 0.008;
                $x1 = max(0.0, $x1 - $pad);
                $y1 = max(0.0, $y1 - $pad);
                $x2 = min(1.0, $x2 + $pad);
                $y2 = min(1.0, $y2 + $pad);
                $rects[] = ['p' => $p, 'x' => round($x1, 4), 'y' => round($y1, 4), 'w' => round($x2 - $x1, 4), 'h' => round($y2 - $y1, 4)];
            }
        }
        usort($rects, fn ($a, $b) => [$a['p'], $a['y']] <=> [$b['p'], $b['y']]);

        return $rects;
    }

    /** Pasang kotak ke tiap blok pratinjau. Blok kosong (bagian yang belum ada di CV) tidak punya kotak. */
    public function attach(array $blocks, array $pages): array
    {
        $segments = $this->segments($pages);
        foreach ($blocks as &$b) {
            $b['rects'] = ! empty($b['ghost']) ? [] : $this->rects($segments, in_array($b['key'], ['kontak', 'isi'], true) ? null : $b['key']);
        }
        unset($b);

        return $blocks;
    }

    private function maxHeight(array $seg): float
    {
        return $seg['items'] === [] ? 0.0 : max(array_map(fn ($it) => $it['l']['h'], $seg['items']));
    }

    /**
     * Cari batas dua kolom: posisi x di mana hampir tidak ada baris yang melintas, dengan kedua sisi cukup berisi.
     * Null bila halaman satu kolom.
     */
    private function split(array $lines): ?float
    {
        $n = count($lines);
        if ($n < 8) {
            return null;
        }

        $cands = [];
        foreach ($lines as $ln) {
            if ($ln['x'] > 0.28 && $ln['x'] < 0.72) {
                $cands[(string) round($ln['x'], 3)] = round($ln['x'], 3);
            }
        }

        $best = null;
        $bestScore = null;
        foreach ($cands as $c) {
            $left = $right = $cross = 0;
            foreach ($lines as $ln) {
                if ($ln['x'] >= $c - 0.004) {
                    $right++;
                } elseif ($ln['x'] + $ln['w'] > $c - 0.004) {
                    $cross++;
                } else {
                    $left++;
                }
            }
            if ($left < 0.2 * $n || $right < 0.2 * $n || $cross > 0.08 * $n) {
                continue;
            }
            $score = $cross * 10 - min($left, $right) / $n;
            if ($bestScore === null || $score < $bestScore) {
                $best = $c;
                $bestScore = $score;
            }
        }

        return $best;
    }
}
