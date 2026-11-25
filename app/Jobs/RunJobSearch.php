<?php

namespace App\Jobs;

use App\Services\Jobs\JobFinder;
use App\Services\Jobs\JobInsights;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

/** Menjalankan satu pencarian lowongan dan menulis kemajuannya ke cache (dibaca lewat halaman status). */
class RunJobSearch
{
    use Dispatchable;

    public const TTL = 21600; // hasil disimpan 6 jam

    public function __construct(public string $id, public array $params)
    {
    }

    public static function key(string $id): string
    {
        return "jobsearch:{$id}";
    }

    public static function state(string $id): ?array
    {
        return Cache::get(self::key($id));
    }

    public function handle(JobFinder $finder, JobInsights $insights): void
    {
        set_time_limit(240);
        $state = self::state($this->id) ?? [];
        $save = function (array $patch) use (&$state) {
            $state = array_merge($state, $patch);
            Cache::put(self::key($this->id), $state, self::TTL);
        };
        $warnings = [];

        try {
            $results = $finder->run($this->params, fn (string $msg, int $pct) => $save(['msg' => $msg, 'pct' => $pct]), $warnings);
            $min = (int) config('platform.jobs.min_match', 25);
            $analysis = $insights->analyze($results, $this->params, $min);
            // Daftar yang dikirim ke browser tanpa data bantu analisis.
            $rows = array_map(fn ($j) => array_diff_key($j, ['demanded' => 1, 'raw_id' => 1, 'needs_detail' => 1, 'id' => 1]), $results);
            $save(['status' => 'done', 'pct' => 100, 'msg' => 'Selesai', 'results' => $rows, 'insights' => $analysis, 'warnings' => $warnings]);
        } catch (\Throwable $e) {
            report($e);
            $save(['status' => 'error', 'msg' => 'Pencarian gagal. Coba lagi sebentar lagi.', 'warnings' => $warnings]);
        } finally {
            Cache::decrement('jobsearch:running');
        }
    }
}
