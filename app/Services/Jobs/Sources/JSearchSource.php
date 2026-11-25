<?php

namespace App\Services\Jobs\Sources;

class JSearchSource extends Source
{
    public function key(): string { return 'jsearch'; }

    public function label(): string { return 'JSearch'; }

    public function note(): string { return 'Google for Jobs: gabungan banyak situs karier'; }

    public function available(): bool { return (bool) config('platform.jobs.jsearch_key'); }

    public function fetch(array $q): array
    {
        $key = config('platform.jobs.jsearch_key');
        $magang = $q['kind'] === 'magang';
        $period = [24 => 'today', 168 => 'week', 720 => 'month'][$q['hours']] ?? 'week';
        $combos = [];
        foreach (array_slice($q['positions'], 0, 2) as $p) {
            foreach (array_slice($q['locations'], 0, 2) as $l) {
                $combos[] = [$p, $l];
            }
        }

        // Hemat kuota gratis: maksimal 3 query.
        return $this->each(array_slice($combos, 0, 3), function (array $c) use ($key, $magang, $period) {
            $res = $this->http(30)->withHeaders(['X-RapidAPI-Key' => $key, 'X-RapidAPI-Host' => 'jsearch.p.rapidapi.com'])
                ->get('https://jsearch.p.rapidapi.com/search', array_filter([
                    'query' => trim("{$c[0]} ".($magang ? 'internship ' : '')."in {$c[1]}"), 'page' => 1, 'num_pages' => 1,
                    'date_posted' => $period, 'employment_types' => $magang ? 'INTERN' : null,
                ]))->throw()->json('data') ?? [];

            return array_map(fn ($j) => $this->job((string) ($j['job_id'] ?? ''), $j['job_title'] ?? '', $j['employer_name'] ?? '',
                implode(', ', array_filter([$j['job_city'] ?? null, $j['job_state'] ?? null, $j['job_country'] ?? null])), $j['job_apply_link'] ?? '', [
                    'posted' => substr((string) ($j['job_posted_at_datetime_utc'] ?? ''), 0, 10), 'logo' => $j['employer_logo'] ?? '',
                    'job_type' => $j['job_employment_type'] ?? '', 'description' => $j['job_description'] ?? '',
                ]), $res);
        });
    }
}
