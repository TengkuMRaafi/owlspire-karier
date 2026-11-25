<?php

namespace App\Services\Careers;

use App\Contracts\JobSourceAdapter;
use App\Services\Jobs\Sources\ArbeitnowSource;
use App\Services\Jobs\Sources\JobicySource;
use App\Services\Jobs\Sources\LinkedInSource;
use App\Services\Jobs\Sources\RemoteOkSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Mengumpulkan permintaan pasar per karier dari API lowongan resmi.
 * Semua sumber tanpa API key: LinkedIn (pasar Indonesia), Jobicy, RemoteOK, dan Arbeitnow.
 */
class CareerDemandCollector
{
    /** Sumber berupa feed tunggal (satu panggilan, disaring sendiri). Sisanya dicari per karier. */
    private const FEEDS = ['remoteok', 'arbeitnow'];

    /** @param JobSourceAdapter[]|null $adapters */
    public function __construct(private ?array $adapters = null) {}

    /** @return JobSourceAdapter[] */
    public function adapters(): array
    {
        $official = (array) config('platform.trending.job_sources', []);
        $all = $this->adapters ?? [new LinkedInSource(), new JobicySource(), new RemoteOkSource(), new ArbeitnowSource()];

        return array_values(array_filter($all, fn (JobSourceAdapter $a) => $a->available() && in_array($a->key(), $official, true)));
    }

    /**
     * @param  Collection<int, \App\Models\Career>  $careers
     * @return array{counts: array<int, array{jobs:int, sources:array<string,int>}>, ok: string[], failed: array<string,string>, pool:int, unmatched:int, unmatched_sample: string[]}
     */
    public function collect(Collection $careers): array
    {
        $keywords = $this->keywords($careers);
        $base = ['skills' => [], 'locations' => [(string) config('career_trend.location', 'Indonesia')], 'hours' => 168, 'kind' => 'kerja'];
        $pool = [];
        $ok = [];
        $failed = [];

        foreach ($this->adapters() as $adapter) {
            try {
                $jobs = in_array($adapter->key(), self::FEEDS, true)
                    ? $adapter->fetch($base + ['positions' => []])
                    : $this->perCareer($adapter, $careers, $keywords, $base);
                // LinkedIn tidak melempar error saat diblokir/dibatasi, hanya mengembalikan daftar kosong; hasil kosong total = gagal.
                if ($adapter->key() === 'linkedin' && ! $jobs) {
                    throw new RuntimeException('LinkedIn tidak mengembalikan lowongan (kemungkinan dibatasi atau diblokir)');
                }
            } catch (Throwable $e) {
                $failed[$adapter->key()] = $e->getMessage();

                continue;
            }
            $ok[] = $adapter->key();
            foreach ($jobs as $j) {
                if (! $this->fresh($j)) {
                    continue;
                }
                $k = mb_strtolower(trim(($j['title'] ?? '').'|'.($j['company'] ?? '')));
                $pool[$k]['title'] ??= (string) ($j['title'] ?? '');
                $pool[$k]['sources'][$adapter->key()] = true;
            }
        }

        if (! $ok) {
            throw new RuntimeException('Semua sumber gagal: '.json_encode($failed, JSON_UNESCAPED_UNICODE));
        }

        $counts = [];
        foreach ($careers as $c) {
            $counts[$c->id] = ['jobs' => 0, 'sources' => []];
        }
        $unmatched = [];
        foreach ($pool as $item) {
            $id = $this->match($item['title'], $keywords);
            if ($id === null) {
                $unmatched[] = $item['title'];

                continue;
            }
            $counts[$id]['jobs']++;
            foreach (array_keys($item['sources']) as $s) {
                $counts[$id]['sources'][$s] = ($counts[$id]['sources'][$s] ?? 0) + 1;
            }
        }

        return [
            'counts' => $counts, 'ok' => $ok, 'failed' => $failed, 'pool' => count($pool),
            'unmatched' => count($unmatched), 'unmatched_sample' => array_slice($unmatched, 0, 15),
        ];
    }

    /** @return array<int, string[]> career id => kata kunci huruf kecil */
    public function keywords(Collection $careers): array
    {
        $map = (array) config('career_trend.keywords', []);
        $out = [];
        foreach ($careers as $c) {
            $list = $map[$c->slug] ?? [$c->name];
            $out[$c->id] = array_values(array_unique(array_map(fn ($k) => mb_strtolower(trim($k)), $list)));
        }

        return $out;
    }

    /** Karier yang kata kuncinya paling panjang cocok dengan judul; null bila tidak ada. */
    public function match(string $title, array $keywords): ?int
    {
        $t = mb_strtolower($title);
        $best = null;
        $bestLen = 0;
        foreach ($keywords as $id => $list) {
            foreach ($list as $k) {
                if ($k !== '' && str_contains($t, $k) && mb_strlen($k) > $bestLen) {
                    $best = $id;
                    $bestLen = mb_strlen($k);
                }
            }
        }

        return $best;
    }

    private function perCareer(JobSourceAdapter $adapter, Collection $careers, array $keywords, array $base): array
    {
        $take = max(1, (int) (config('career_trend.queries_per_career')[$adapter->key()] ?? 1));
        $pause = (int) config('career_trend.pause_ms', 300);
        $out = [];
        $errors = 0;
        $last = null;

        foreach ($careers as $c) {
            try {
                $out = array_merge($out, $adapter->fetch($base + ['positions' => array_slice($keywords[$c->id], 0, $take)]));
            } catch (Throwable $e) {
                $errors++;
                $last = $e;
            }
            if ($pause > 0) {
                usleep($pause * 1000);
            }
        }
        if ($last && $errors === $careers->count()) {
            throw $last;
        }

        return $out;
    }

    private function fresh(array $job): bool
    {
        $posted = (string) ($job['posted'] ?? '');
        if ($posted === '') {
            return true;
        }
        try {
            return Carbon::parse($posted)->greaterThanOrEqualTo(now()->subDays((int) config('platform.trending.job_days', 30)));
        } catch (Throwable) {
            return true;
        }
    }
}
