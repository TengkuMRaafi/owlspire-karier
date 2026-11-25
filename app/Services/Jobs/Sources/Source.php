<?php

namespace App\Services\Jobs\Sources;

use App\Contracts\JobSourceAdapter;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Dasar semua sumber: HTTP, pembersihan teks, dan bentuk lowongan yang seragam. */
abstract class Source implements JobSourceAdapter
{
    public function defaultOn(): bool
    {
        return false;
    }

    public function available(): bool
    {
        return true;
    }

    protected function http(int $timeout = 25): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Owlspire/1.0'])->timeout($timeout);
    }

    public static function clean(mixed $v): string
    {
        if (is_array($v)) {
            $v = implode(', ', array_map('strval', $v));
        }
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '</p>', '</li>', '</div>'], ' ', (string) $v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** Bentuk lowongan yang dipakai seluruh sistem. */
    protected function job(string $id, mixed $title, mixed $company, mixed $location, ?string $url, array $more = []): array
    {
        return array_merge([
            'source' => $this->label(), 'id' => $this->key().'-'.$id,
            'title' => self::clean($title) ?: '-', 'company' => self::clean($company) ?: '-',
            'location' => self::clean($location) ?: '-', 'url' => (string) $url,
            'posted' => '', 'logo' => '', 'job_type' => '', 'seniority' => '', 'industry' => '', 'description' => '',
        ], array_map(fn ($v) => is_string($v) || is_array($v) ? self::clean($v) : $v, array_diff_key($more, ['logo' => 1, 'posted' => 1])), [
            'logo' => (string) ($more['logo'] ?? ''), 'posted' => (string) ($more['posted'] ?? ''),
        ]);
    }

    /** Jalankan beberapa query; satu gagal tidak menggugurkan yang lain. */
    protected function each(array $queries, callable $fn): array
    {
        $out = [];
        $err = null;
        foreach ($queries as $q) {
            try {
                $out = array_merge($out, $fn($q));
            } catch (\Throwable $e) {
                $err = $e;
            }
        }
        if ($err && ! $out) {
            throw $err;
        }

        return $out;
    }
}
