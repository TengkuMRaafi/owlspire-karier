<?php

namespace App\Services\Telegram;

use Carbon\Carbon;

/** Kartu lowongan berformat HTML untuk Telegram. Satu tempat supaya /cari dan kiriman terjadwal tampil sama. */
class JobCard
{
    public static function e(mixed $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param iterable<array> $jobs */
    public static function render(iterable $jobs, string $header): string
    {
        $cards = [];
        foreach (collect($jobs)->values() as $i => $j) {
            $cards[] = self::card($j, $i + 1);
        }

        return $header."\n\n".implode("\n\n", $cards);
    }

    private static function card(array $j, int $n): string
    {
        $m = (int) ($j['match'] ?? 0);
        $dot = $m >= 70 ? '🟢' : ($m >= 40 ? '🟡' : '🟠');
        $title = self::e($j['title'] ?? '-');
        $url = (string) ($j['url'] ?? '');

        $out = "<b>{$n}. ".($url !== '' ? '<a href="'.self::e($url).'">'.$title.'</a>' : $title).'</b>';
        $out .= "\n🏢 ".self::e($j['company'] ?? '-').' · 📍 '.self::e($j['location'] ?? '-');

        $meta = array_filter([
            $m > 0 ? "{$dot} {$m}% cocok" : null,
            self::ago((string) ($j['posted'] ?? '')),
            self::e($j['job_type'] ?? ''),
            self::e($j['seniority'] ?? ''),
        ]);
        if ($meta) {
            $out .= "\n".implode(' · ', $meta);
        }

        $skills = array_slice((array) ($j['matched'] ?? []), 0, 4);
        if ($skills) {
            $out .= "\n✅ ".self::e(implode(', ', $skills));
        }

        return $out;
    }

    private static function ago(string $posted): ?string
    {
        if ($posted === '') {
            return null;
        }
        try {
            return '🕒 '.self::e(Carbon::parse($posted)->locale('id')->diffForHumans());
        } catch (\Throwable) {
            return '🕒 '.self::e($posted); // bukan tanggal (mis. "5 jam yang lalu"): tampilkan apa adanya
        }
    }
}
