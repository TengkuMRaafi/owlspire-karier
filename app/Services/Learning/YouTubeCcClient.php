<?php

namespace App\Services\Learning;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Http;

/** Mencari video YouTube berlisensi Creative Commons yang boleh disematkan (YouTube Data API */
class YouTubeCcClient
{
    private const BASE = 'https://www.googleapis.com/youtube/v3';

    public function configured(): bool
    {
        return (string) config('services.youtube.key') !== '';
    }

    /** @return array<int, array{video_id:string,title:string,channel:string,minutes:?int,license:string,url:string}> */
    public function search(string $query, int $max = 3, bool $ccOnly = true): array
    {
        $key = config('services.youtube.key');
        if (! $key) {
            throw new \RuntimeException('YOUTUBE_API_KEY belum diisi di .env.');
        }

        $found = Http::timeout(20)->get(self::BASE.'/search', array_filter([
            'part' => 'snippet', 'type' => 'video', 'q' => $query, 'videoLicense' => $ccOnly ? 'creativeCommon' : null,
            'videoEmbeddable' => 'true', 'relevanceLanguage' => 'id', 'safeSearch' => 'strict',
            'maxResults' => min(10, max(1, $max * 2)), 'key' => $key,
        ]));
        if (! $found->successful()) {
            throw new \RuntimeException('YouTube API error: '.($found->json('error.message') ?? $found->status()));
        }

        $ids = collect($found->json('items', []))->pluck('id.videoId')->filter()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $detail = Http::timeout(20)->get(self::BASE.'/videos', [
            'part' => 'snippet,contentDetails,status', 'id' => $ids->implode(','), 'key' => $key,
        ]);
        if (! $detail->successful()) {
            throw new \RuntimeException('YouTube API error: '.($detail->json('error.message') ?? $detail->status()));
        }

        return collect($detail->json('items', []))
            ->filter(fn ($v) => (! $ccOnly || ($v['status']['license'] ?? '') === 'creativeCommon')
                && ($v['status']['embeddable'] ?? false) === true
                && ($v['status']['privacyStatus'] ?? '') === 'public')
            ->take($max)
            ->map(fn ($v) => [
                'video_id' => $v['id'],
                'title' => $v['snippet']['title'] ?? 'Video',
                'channel' => $v['snippet']['channelTitle'] ?? '',
                'minutes' => $this->minutes($v['contentDetails']['duration'] ?? null),
                'license' => $ccOnly ? 'Creative Commons Attribution (CC BY)' : 'Disematkan lewat pemutar resmi YouTube',
                'url' => 'https://www.youtube.com/watch?v='.$v['id'],
            ])->values()->all();
    }

    private function minutes(?string $iso): ?int
    {
        try {
            return $iso ? max(1, (int) round(CarbonInterval::make($iso)->totalMinutes)) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Mengambil ID video dari URL watch, youtu.be, embed, atau ID polos. */
    public static function parseId(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
            return $input;
        }
        if (preg_match('~(?:v=|youtu\.be/|/embed/|/shorts/)([A-Za-z0-9_-]{11})~', $input, $m)) {
            return $m[1];
        }

        return null;
    }
}
