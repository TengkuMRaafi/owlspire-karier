<?php

namespace App\Services\Jobs\Sources;

class JobicySource extends Source
{
    public function key(): string { return 'jobicy'; }

    public function label(): string { return 'Jobicy'; }

    public function note(): string { return 'Remote global, beragam bidang'; }

    public function fetch(array $q): array
    {
        return $this->each(array_slice($q['positions'], 0, 3), function (string $p) {
            $data = $this->http()->get('https://jobicy.com/api/v2/remote-jobs', ['count' => 50, 'tag' => $p])->throw()->json('jobs') ?? [];

            return array_map(fn ($j) => $this->job((string) ($j['id'] ?? ''), $j['jobTitle'] ?? '', $j['companyName'] ?? '', $j['jobGeo'] ?? 'Remote', $j['url'] ?? '', [
                'posted' => substr((string) ($j['pubDate'] ?? ''), 0, 10), 'logo' => $j['companyLogo'] ?? '',
                'job_type' => $j['jobType'] ?? '', 'seniority' => $j['jobLevel'] ?? '', 'industry' => $j['jobIndustry'] ?? '',
                'description' => $j['jobDescription'] ?? $j['jobExcerpt'] ?? '',
            ]), $data);
        });
    }
}
