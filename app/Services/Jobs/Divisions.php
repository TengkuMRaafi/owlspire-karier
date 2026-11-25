<?php

namespace App\Services\Jobs;

/** Deteksi bidang kerja dan kedekatan judul lowongan (port dari divisions.py job_finder). */
class Divisions
{
    private array $data;

    private array $div = [];

    private string $allTerms;

    private array $groupRe = [];

    private array $synEntries = [];

    public function __construct()
    {
        $this->data = json_decode((string) file_get_contents(resource_path('data/job-divisions.json')), true);
        foreach ($this->data['divisions'] as $name => $d) {
            $this->div[$name] = ['title' => $this->alt($d['titles'], true), 'term' => $this->alt($d['terms'], false)];
        }
        $this->allTerms = $this->alt(array_merge(...array_column($this->data['divisions'], 'terms')), false);
        foreach ($this->data['syn'] as $gi => $group) {
            $this->groupRe[$gi] = $this->alt($group, true);
            foreach ($group as $v) {
                $this->synEntries[] = [$v, $gi];
            }
        }
        usort($this->synEntries, fn ($a, $b) => mb_strlen($b[0]) <=> mb_strlen($a[0]));
    }

    /** Satu regex untuk banyak kata; flex membolehkan akhiran 1-3 huruf (design -> designer). */
    private function alt(array $words, bool $flex): string
    {
        $ws = array_values(array_unique(array_map('mb_strtolower', array_filter($words))));
        usort($ws, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $parts = array_map(fn ($w) => preg_quote($w, '/').($flex && mb_strlen($w) > 3 ? '[a-z]{0,3}' : ''), $ws);

        return '/(?<![a-z0-9])(?:'.implode('|', $parts).')(?![a-z0-9])/u';
    }

    private function clean(mixed $t): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower((string) $t)));
    }

    private function count(string $rx, string $text): int
    {
        preg_match_all($rx, $text, $m);

        return count(array_unique($m[0]));
    }

    private function detect(array $positions, array $skills, int $limit = 2): array
    {
        $pos = $this->clean(implode(' ', $positions));
        $sk = $this->clean(implode(' ', $skills));
        $scores = [];
        foreach ($this->div as $name => $rx) {
            $s = 3 * $this->count($rx['title'], $pos) + $this->count($rx['term'], $pos.' '.$sk) + $this->count($rx['title'], $sk);
            if ($s >= 3) {
                $scores[$name] = $s;
            }
        }
        if (! $scores) {
            return [];
        }
        $top = max($scores);
        $ranked = array_keys(array_filter($scores, fn ($v) => $v >= 0.5 * $top));
        usort($ranked, fn ($a, $b) => $scores[$b] <=> $scores[$a]);

        return array_slice($ranked, 0, $limit);
    }

    /** Pecah satu posisi jadi konsep: kelompok sinonim + kata biasa yang bukan kata jabatan umum. */
    private function concepts(string $position): array
    {
        preg_match_all('/[a-z0-9+#&.\/]+/', mb_strtolower($position), $m);
        $p = ' '.implode(' ', array_map(fn ($t) => trim($t, './&,'), $m[0])).' ';
        $out = [];
        foreach ($this->synEntries as [$v, $gi]) {
            $pat = ' '.$v.' ';
            if (str_contains($p, $pat)) {
                if (! in_array($this->groupRe[$gi], $out, true)) {
                    $out[] = $this->groupRe[$gi];
                }
                while (str_contains($p, $pat)) {
                    $p = str_replace($pat, ' ', $p);
                }
            }
        }
        $words = array_values(array_filter(preg_split('/\s+/', trim($p)), fn ($t) => $t !== '' && ! in_array($t, $this->data['stop'], true)));
        $plain = array_values(array_filter($words, fn ($t) => ! in_array($t, $this->data['generic'], true)));
        foreach ($plain ?: ($out ? [] : $words) as $t) {
            $out[] = $this->alt([$t], true);
        }

        return $out;
    }

    /** Dihitung sekali per pencarian lalu dipakai untuk semua lowongan. */
    public function context(array $positions, array $skills = []): array
    {
        $divs = $this->detect($positions, $skills);
        $concepts = array_values(array_filter(array_map(fn ($p) => trim((string) $p) === '' ? [] : $this->concepts((string) $p), $positions)));

        return [
            'divs' => $divs,
            'label' => $divs[0] ?? '',
            'concepts' => $concepts,
            'title_re' => array_map(fn ($d) => $this->div[$d]['title'], $divs),
            'term_re' => array_map(fn ($d) => $this->div[$d]['term'], $divs),
        ];
    }

    /** 0..35: judul sama persis dengan posisi dicari = 35, hanya sebidang = 12, tidak terkait = 0. */
    public function titlePoints(string $title, array $ctx): int
    {
        $t = $this->clean($title);
        $best = 0.0;
        foreach ($ctx['concepts'] as $concepts) {
            $hits = count(array_filter($concepts, fn ($rx) => preg_match($rx, $t)));
            $best = max($best, $hits / count($concepts));
        }
        $pts = $best >= 0.33 ? (int) round(35 * $best) : 0;
        if ($pts < 12) {
            foreach ($ctx['title_re'] as $rx) {
                if (preg_match($rx, $t)) {
                    return 12;
                }
            }
        }

        return $pts;
    }

    /** Istilah khas bidang pengguna yang muncul di lowongan, di luar skill yang sudah dihitung. */
    public function related(string $text, array $ctx, array $exclude = []): array
    {
        $t = $this->clean($text);
        $ex = array_map('mb_strtolower', $exclude);
        $out = [];
        foreach ($ctx['term_re'] as $rx) {
            preg_match_all($rx, $t, $m);
            foreach ($m[0] as $w) {
                if (! in_array($w, $out, true) && ! in_array($w, $ex, true)) {
                    $out[] = $w;
                }
            }
        }

        return $out;
    }

    /** Label bidang sebuah lowongan; bila tidak jelas pakai bidang pencarian, atau "Umum". */
    public function classify(string $title, string $text, string $fallback = ''): string
    {
        $t = $this->clean($title);
        $x = $this->clean($text);
        $best = '';
        $bs = 0;
        foreach ($this->div as $name => $rx) {
            $s = 3 * $this->count($rx['title'], $t) + min(4, $this->count($rx['term'], $x));
            if ($s > $bs) {
                [$best, $bs] = [$name, $s];
            }
        }

        return $bs >= 3 ? $best : ($fallback ?: 'Umum');
    }

    /** Istilah keahlian (semua bidang) yang diminta sebuah lowongan. */
    public function demanded(string $text): array
    {
        preg_match_all($this->allTerms, $this->clean($text), $m);

        return array_values(array_unique($m[0]));
    }
}
