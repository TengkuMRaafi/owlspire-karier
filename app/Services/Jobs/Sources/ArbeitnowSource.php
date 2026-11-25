<?php

namespace App\Services\Jobs\Sources;

class ArbeitnowSource extends Source
{
    public function key(): string { return 'arbeitnow'; }

    public function label(): string { return 'Arbeitnow'; }

    public function note(): string { return 'Eropa + remote'; }

    public function fetch(array $q): array
    {
        $locs = array_map('mb_strtolower', $q['locations']);
        $out = [];
        foreach ($this->http()->get('https://www.arbeitnow.com/api/job-board-api')->throw()->json('data') ?? [] as $j) {
            $loc = (string) ($j['location'] ?? '');
            $match = false;
            foreach ($locs as $l) {
                $match = $match || str_contains(mb_strtolower($loc), $l);
            }
            if (empty($j['remote']) && ! $match) {
                continue;
            }
            $ts = $j['created_at'] ?? null;
            $out[] = $this->job((string) ($j['slug'] ?? ''), $j['title'] ?? '', $j['company_name'] ?? '', $loc ?: 'Remote', $j['url'] ?? '', [
                'posted' => is_numeric($ts) ? gmdate('Y-m-d', (int) $ts) : '',
                'job_type' => $j['job_types'] ?? (! empty($j['remote']) ? 'Remote' : ''), 'description' => $j['description'] ?? '',
            ]);
        }

        return $out;
    }
}
