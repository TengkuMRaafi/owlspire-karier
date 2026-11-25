<?php

namespace App\Services\Jobs\Sources;

class JoobleSource extends Source
{
    public function key(): string { return 'jooble'; }

    public function label(): string { return 'Jooble'; }

    public function note(): string { return 'Agregator 60+ negara, termasuk Indonesia'; }

    public function defaultOn(): bool { return true; }

    public function available(): bool { return (bool) config('platform.jobs.jooble_key'); }

    public function fetch(array $q): array
    {
        $key = config('platform.jobs.jooble_key');
        $combos = [];
        foreach (array_slice($q['positions'], 0, 3) as $p) {
            foreach (array_slice($q['locations'], 0, 2) as $l) {
                $combos[] = [$p, $l];
            }
        }

        return $this->each($combos, function (array $c) use ($key, $q) {
            $res = $this->http()->post("https://jooble.org/api/{$key}", [
                'keywords' => $c[0].($q['kind'] === 'magang' ? ' magang' : ''), 'location' => $c[1],
            ])->throw()->json('jobs') ?? [];

            return array_map(fn ($j) => $this->job((string) ($j['id'] ?? ''), $j['title'] ?? '', $j['company'] ?? '', $j['location'] ?? '', $j['link'] ?? '', [
                'posted' => substr((string) ($j['updated'] ?? ''), 0, 10), 'job_type' => $j['type'] ?? '', 'description' => $j['snippet'] ?? '',
            ]), $res);
        });
    }
}
