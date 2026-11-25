<?php

namespace App\Services\Jobs\Sources;

class RemoteOkSource extends Source
{
    public function key(): string { return 'remoteok'; }

    public function label(): string { return 'RemoteOK'; }

    public function note(): string { return 'Remote global, dominan tech'; }

    public function fetch(array $q): array
    {
        $out = [];
        foreach ($this->http()->get('https://remoteok.com/api')->throw()->json() ?? [] as $j) {
            if (! is_array($j) || empty($j['position'])) {
                continue; // elemen pertama = pemberitahuan legal
            }
            $out[] = $this->job((string) ($j['id'] ?? ''), $j['position'], $j['company'] ?? '', $j['location'] ?? 'Remote', $j['url'] ?? '', [
                'posted' => substr((string) ($j['date'] ?? ''), 0, 10), 'logo' => $j['logo'] ?? $j['company_logo'] ?? '',
                'job_type' => 'Remote', 'description' => $j['description'] ?? '',
            ]);
        }

        return $out;
    }
}
