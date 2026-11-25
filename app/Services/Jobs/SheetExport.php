<?php

namespace App\Services\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Mengirim daftar lowongan ke Google Apps Script, yang membuat Google Sheet (hanya-baca lewat link) dan mengembalikan URL-nya. Null bila belum diatur atau gagal. */
class SheetExport
{
    public static function make(iterable $jobs, string $title): ?string
    {
        $url = config('services.jobs_sheet.url');
        if (! filled($url)) {
            return null;
        }

        $rows = [['No', 'Posisi', 'Perusahaan', 'Lokasi', 'Cocok %', 'Skill cocok', 'Tipe', 'Level', 'Diposting', 'Sumber', 'Link']];
        foreach (collect($jobs)->values() as $i => $j) {
            $rows[] = [$i + 1, $j['title'] ?? '', $j['company'] ?? '', $j['location'] ?? '', (int) ($j['match'] ?? 0),
                implode(', ', (array) ($j['matched'] ?? [])), $j['job_type'] ?? '', $j['seniority'] ?? '', $j['posted'] ?? '', $j['source'] ?? '', $j['url'] ?? ''];
        }

        try {
            $res = Http::timeout(25)->post($url, ['secret' => config('services.jobs_sheet.secret'), 'title' => $title.' - '.now()->format('d M Y H:i'), 'rows' => $rows]);
            $link = trim($res->body());

            return $res->successful() && str_starts_with($link, 'https://docs.google.com/') ? $link : null;
        } catch (\Throwable $e) {
            Log::warning('Export Google Sheet gagal: '.$e->getMessage());

            return null;
        }
    }
}
