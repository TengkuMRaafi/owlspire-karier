<?php

namespace App\Services\Jobs\Sources;

use DOMDocument;
use DOMElement;
use DOMXPath;

/** LinkedIn lewat endpoint publik tanpa login. Daftar dulu, deskripsi dibaca terpisah (lambat, dibatasi). */
class LinkedInSource extends Source
{
    private const SEARCH = 'https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search';

    private const DETAIL = 'https://www.linkedin.com/jobs-guest/jobs/api/jobPosting/';

    public function key(): string { return 'linkedin'; }

    public function label(): string { return 'LinkedIn'; }

    public function note(): string { return 'Lowongan umum, termasuk lokal Indonesia'; }

    public function defaultOn(): bool { return true; }

    /** Hasil pencarian tanpa deskripsi; deskripsi diisi lewat detail(). */
    public function fetch(array $q): array
    {
        $combos = [];
        foreach ($q['positions'] as $p) {
            foreach ($q['locations'] as $l) {
                $combos[] = [$p, $l];
            }
        }
        $batches = array_map(fn ($c) => $this->search($c[0], $c[1], $q['hours'], $q['kind']), array_slice($combos, 0, 8));

        // Selang-seling antar posisi supaya jatah baca deskripsi tidak habis di posisi pertama.
        $out = [];
        for ($k = 0, $max = max(array_map('count', $batches) ?: [0]); $k < $max; $k++) {
            foreach ($batches as $b) {
                if (isset($b[$k])) {
                    $out[] = $b[$k];
                }
            }
        }

        return $out;
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($dom);
    }

    private function cls(string $c): string
    {
        return "contains(concat(' ', normalize-space(@class), ' '), ' {$c} ')";
    }

    private function text(?\DOMNode $n): string
    {
        return $n ? self::clean($n->textContent) : '';
    }

    private function search(string $keyword, string $location, int $hours, string $kind): array
    {
        $params = ['keywords' => $keyword, 'location' => $location, 'f_TPR' => 'r'.($hours * 3600), 'start' => 0];
        if ($kind === 'magang') {
            $params['f_JT'] = 'I';
        }
        $res = $this->http(20)->withHeaders(['Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8'])->get(self::SEARCH, $params);
        if (! $res->successful()) {
            return [];
        }
        $xp = $this->xpath($res->body());
        $jobs = [];
        foreach ($xp->query('//li') as $li) {
            $link = $xp->query('.//a['.$this->cls('base-card__full-link').']', $li)->item(0);
            $title = $xp->query('.//*['.$this->cls('base-search-card__title').']', $li)->item(0);
            $urn = $xp->query('.//*[@data-entity-urn]', $li)->item(0);
            if (! ($link instanceof DOMElement && $title && $urn instanceof DOMElement)) {
                continue;
            }
            $img = $xp->query('.//img', $li)->item(0);
            $logo = $img instanceof DOMElement ? ($img->getAttribute('data-delayed-url') ?: $img->getAttribute('src')) : '';
            $time = $xp->query('.//time', $li)->item(0);
            $id = last(explode(':', $urn->getAttribute('data-entity-urn')));
            $jobs[] = $this->job($id, $this->text($title), $this->text($xp->query('.//*['.$this->cls('base-search-card__subtitle').']', $li)->item(0)),
                $this->text($xp->query('.//*['.$this->cls('job-search-card__location').']', $li)->item(0)), explode('?', $link->getAttribute('href'))[0], [
                    'posted' => $time instanceof DOMElement ? substr($time->getAttribute('datetime'), 0, 10) : '',
                    'logo' => str_starts_with($logo, 'data:') ? '' : $logo,
                ]) + ['raw_id' => $id, 'needs_detail' => true];
        }

        return $jobs;
    }

    /** Deskripsi dan kriteria (jenis, senioritas, industri) satu lowongan. */
    public function detail(string $id): array
    {
        $out = ['description' => '', 'seniority' => '', 'job_type' => '', 'industry' => ''];
        try {
            $res = $this->http(20)->withHeaders(['Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8'])->get(self::DETAIL.$id);
        } catch (\Throwable) {
            return $out;
        }
        if (! $res->successful()) {
            return $out;
        }
        $xp = $this->xpath($res->body());
        $out['description'] = $this->text($xp->query('//*['.$this->cls('show-more-less-html__markup').']')->item(0));
        $map = ['seniority' => ['seniority', 'senioritas'], 'job_type' => ['employment', 'jenis pekerjaan', 'jenis pekerja'], 'industry' => ['industr']];
        foreach ($xp->query('//li['.$this->cls('description__job-criteria-item').']') as $li) {
            $k = mb_strtolower($this->text($xp->query('.//h3', $li)->item(0)));
            $v = $this->text($xp->query('.//span', $li)->item(0));
            foreach ($map as $field => $keys) {
                foreach ($keys as $needle) {
                    if (str_contains($k, $needle)) {
                        $out[$field] = $v;
                    }
                }
            }
        }

        return $out;
    }
}
