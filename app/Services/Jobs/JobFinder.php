<?php

namespace App\Services\Jobs;

use App\Contracts\JobSourceAdapter;
use App\Services\Jobs\Sources\LinkedInSource;
use Illuminate\Support\Str;

/** Mencari lowongan dari banyak sumber, menggabungkan duplikat, dan mengurutkan menurut kecocokan. */
class JobFinder
{
    /** Sumber feed global yang tidak punya filter magang. */
    private const GLOBAL_FEEDS = ['remoteok', 'arbeitnow', 'jobicy'];

    /** @var array<string, JobSourceAdapter> */
    private array $adapters = [];

    /** @param iterable<JobSourceAdapter> $adapters */
    public function __construct(iterable $adapters, private Divisions $divisions, private JobScorer $scorer)
    {
        foreach ($adapters as $a) {
            $this->adapters[$a->key()] = $a;
        }
    }

    /** Daftar sumber untuk formulir. */
    public function catalog(): array
    {
        return array_values(array_map(fn (JobSourceAdapter $a) => [
            'id' => $a->key(), 'label' => $a->label(), 'note' => $a->note(), 'default' => $a->defaultOn(), 'available' => $a->available(),
        ], $this->adapters));
    }

    public function availableKeys(): array
    {
        return array_keys(array_filter($this->adapters, fn ($a) => $a->available()));
    }

    /**
     * @param array{positions: string[], skills: string[], locations: string[], hours: int, kind: string, sources: string[]} $p
     * @param callable(string, int): void|null $progress
     * @param string[] $warnings diisi pesan sumber yang gagal
     */
    public function run(array $p, ?callable $progress = null, array &$warnings = []): array
    {
        $log = $progress ?? fn () => null;
        $ctx = $this->divisions->context($p['positions'], $p['skills']);
        $seen = [];
        $total = max(1, count($p['sources']));
        $maxDetail = (int) config('platform.jobs.max_detail', 25);

        foreach (array_values($p['sources']) as $n => $key) {
            $adapter = $this->adapters[$key] ?? null;
            if (! $adapter) {
                continue;
            }
            $log("Mengambil dari {$adapter->label()}...", (int) (5 + 35 * $n / $total));
            try {
                $got = $adapter->fetch($p);
                if ($p['kind'] === 'magang' && in_array($key, self::GLOBAL_FEEDS, true)) {
                    $got = array_values(array_filter($got, fn ($j) => $this->isIntern($j)));
                }
                foreach ($got as $j) {
                    $this->add($seen, $j);
                }
                if (! $got) {
                    $warnings[] = "{$adapter->label()}: tidak ada hasil";
                }
            } catch (\Throwable $e) {
                $warnings[] = "{$adapter->label()}: ".Str::limit($e->getMessage(), 80);
            }
        }

        // LinkedIn: baca deskripsi untuk judul yang paling relevan dulu (lambat, jadi dibatasi).
        $linkedin = $this->adapters['linkedin'] ?? null;
        if ($linkedin instanceof LinkedInSource) {
            $keys = array_filter(array_keys($seen), fn ($k) => ! empty($seen[$k]['needs_detail']));
            usort($keys, fn ($a, $b) => $this->divisions->titlePoints($seen[$b]['title'], $ctx) <=> $this->divisions->titlePoints($seen[$a]['title'], $ctx));
            $keys = array_slice($keys, 0, $maxDetail);
            foreach ($keys as $i => $k) {
                $log('Membaca deskripsi LinkedIn '.($i + 1).'/'.count($keys), (int) (40 + 55 * $i / max(1, count($keys))));
                $seen[$k] = array_merge($seen[$k], $linkedin->detail($seen[$k]['raw_id']), ['needs_detail' => false]);
                usleep(random_int(500_000, 1_100_000));
            }
        }

        $results = [];
        foreach ($seen as $j) {
            if (! empty($j['needs_detail'])) {
                continue;
            }
            $scored = $this->scorer->score($j, $p['skills'], $ctx);
            if ($scored['match'] > 0 || $scored['source'] === 'LinkedIn') {
                $results[] = $scored;
            }
        }
        usort($results, fn ($a, $b) => $b['match'] <=> $a['match']);

        return $results;
    }

    private function add(array &$seen, array $j): void
    {
        if (empty($j['url'])) {
            return;
        }
        $k = preg_replace('/\W+/u', '', mb_strtolower($j['title'].$j['company']));
        if (isset($seen[$k])) {
            if (! in_array($j['source'], $seen[$k]['sources'], true)) {
                $seen[$k]['sources'][] = $j['source'];
            }

            return;
        }
        $j['sources'] = [$j['source']];
        $seen[$k] = $j;
    }

    private function isIntern(array $j): bool
    {
        return (bool) preg_match('/(?<![a-z])(intern|interns|internship|magang|praktik kerja|trainee)(?![a-z])/i', ($j['title'] ?? '').' '.($j['job_type'] ?? '').' '.($j['description'] ?? ''));
    }

    /** Tautan pencarian langsung ke situs lain (tanpa scraping). */
    public static function searchLinks(array $positions, array $locations, string $kind): array
    {
        $pos = $positions[0] ?? '';
        $loc = $locations[0] ?? 'Indonesia';
        $anywhere = in_array(mb_strtolower($loc), ['indonesia', 'remote', ''], true);
        $magang = $kind === 'magang';
        $links = [
            ['LinkedIn', 'https://www.linkedin.com/jobs/search/?keywords='.urlencode($pos).'&location='.urlencode($loc).($magang ? '&f_JT=I' : '')],
            ['Indeed', 'https://id.indeed.com/jobs?q='.urlencode($magang ? trim("$pos magang") : $pos).'&l='.urlencode($anywhere ? '' : $loc)],
        ];
        if ($magang) {
            array_splice($links, 1, 0, [['Glints', 'https://glints.com/id/find-jobs/loker-'.(Str::slug($pos) ?: 'magang').'-internship'.($anywhere ? '' : '-'.Str::slug($loc))]]);
        }

        return $links;
    }
}
