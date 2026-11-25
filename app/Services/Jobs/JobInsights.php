<?php

namespace App\Services\Jobs;

/** Ringkasan hasil pencarian: sebaran kecocokan, skill terpakai, skill yang kurang, tips (port dari summary.py). */
class JobInsights
{
    private const GAP_IGNORE = ['english', 'bahasa inggris', 'public speaking', 'negotiation', 'research', 'teaching'];

    private function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($s));
    }

    private function top(array $counts, int $n): array
    {
        arsort($counts);

        return array_map(fn ($k, $v) => [(string) $k, $v], array_keys(array_slice($counts, 0, $n, true)), array_values(array_slice($counts, 0, $n, true)));
    }

    public function analyze(array $jobs, array $params = [], int $minMatch = 25): array
    {
        $shown = array_values(array_filter($jobs, fn ($j) => ($j['match'] ?? 0) >= $minMatch));
        $mine = array_values(array_filter(array_map(fn ($s) => trim((string) $s), $params['skills'] ?? [])));
        $display = [];
        foreach ($mine as $s) {
            $display[mb_strtolower($s)] = $s;
        }

        $usedCnt = [];
        foreach ($shown as $j) {
            foreach ($j['matched'] ?? [] as $s) {
                $k = mb_strtolower($s);
                $usedCnt[$k] = ($usedCnt[$k] ?? 0) + 1;
            }
        }
        arsort($usedCnt);
        $used = array_map(fn ($k, $v) => [$display[$k] ?? $k, $v], array_keys($usedCnt), array_values($usedCnt));
        $unused = array_values(array_filter($mine, fn ($s) => ! isset($usedCnt[mb_strtolower($s)])));

        $mineN = array_map(fn ($s) => $this->norm($s), $mine);
        $gapCnt = [];
        foreach ($shown as $j) {
            foreach ($j['demanded'] ?? [] as $d) {
                $dn = $this->norm($d);
                $skip = in_array(mb_strtolower($d), self::GAP_IGNORE, true);
                foreach ($mineN as $m) {
                    if ($dn === $m || (strlen($m) > 3 && (str_contains($dn, $m) || str_contains($m, $dn)))) {
                        $skip = true;
                    }
                }
                if (! $skip) {
                    $gapCnt[$d] = ($gapCnt[$d] ?? 0) + 1;
                }
            }
        }
        $need = max(2, (int) ceil(0.2 * count($shown)));
        $gap = count($shown) >= 3 ? array_values(array_filter($this->top($gapCnt, 5), fn ($g) => $g[1] >= $need)) : [];

        $hi = count(array_filter($jobs, fn ($j) => ($j['match'] ?? 0) >= 70));
        $mid = count(array_filter($jobs, fn ($j) => ($j['match'] ?? 0) >= 40 && ($j['match'] ?? 0) < 70));

        $locs = [];
        foreach ($shown as $j) {
            $l = trim(explode(',', (string) ($j['location'] ?? ''))[0]);
            if ($l !== '' && $l !== '-') {
                $locs[$l] = ($locs[$l] ?? 0) + 1;
            }
        }
        $comps = [];
        foreach ($shown as $j) {
            if (! in_array($j['company'] ?? '', ['', '-'], true)) {
                $comps[$j['company']] = ($comps[$j['company']] ?? 0) + 1;
            }
        }
        $src = [];
        foreach ($jobs as $j) {
            foreach ($j['sources'] ?? [$j['source'] ?? '-'] as $s) {
                $src[$s] = ($src[$s] ?? 0) + 1;
            }
        }

        $out = [
            'kind' => $params['kind'] ?? 'kerja', 'found' => count($jobs), 'shown' => count($shown), 'min_match' => $minMatch,
            'tiers' => ['hi' => $hi, 'mid' => $mid, 'low' => count($jobs) - $hi - $mid],
            'used' => $used, 'unused' => $unused, 'total_skills' => count($mine), 'gap' => $gap,
            'locations' => $this->top($locs, 3),
            'companies' => count($shown) >= 5 ? array_values(array_filter($this->top($comps, 3), fn ($c) => $c[1] > 1)) : [],
            'sources' => $this->top($src, 6),
        ];
        $out['tips'] = $this->tips($out, $params);

        return $out;
    }

    private function tips(array $a, array $params): array
    {
        $tips = [];
        $hours = $params['hours'] ?? null;
        $locs = array_map('mb_strtolower', $params['locations'] ?? []);
        $narrow = $locs && ! array_intersect($locs, ['indonesia', 'remote']);
        if ($a['shown'] < 3) {
            if ($hours === 24) {
                $tips[] = 'Perluas rentang waktu ke 7 atau 30 hari.';
            } elseif ($hours === 168) {
                $tips[] = 'Coba rentang 30 hari.';
            }
            if ($narrow) {
                $tips[] = 'Tambahkan lokasi Indonesia atau Remote.';
            }
            if ($a['total_skills'] < 3) {
                $tips[] = 'Tambahkan lebih banyak keahlian agar pencocokan lebih akurat.';
            }
            if (count($tips) < 2) {
                $tips[] = 'Coba judul posisi yang lebih umum.';
            }
        }
        if ($a['gap']) {
            $tips[] = 'Mempelajari '.implode(' dan ', array_map(fn ($g) => $g[0], array_slice($a['gap'], 0, 2))).' bisa membuka lebih banyak lowongan yang cocok.';
        } elseif ($a['shown'] >= 3 && $a['tiers']['hi'] === 0) {
            $tips[] = 'Belum ada yang sangat cocok. Tambahkan keahlian spesifik dari bidangmu.';
        }
        if ($a['unused'] && $a['used'] && count($a['unused']) >= max(3, intdiv($a['total_skills'], 2))) {
            $tips[] = 'Banyak keahlianmu belum muncul di hasil. Coba posisi lain yang lebih sesuai.';
        }

        return array_slice($tips, 0, 3);
    }
}
