<?php

namespace App\Jobs;

use App\Services\Jobs\JobFinder;
use App\Services\Jobs\SheetExport;
use App\Services\Telegram\JobCard;
use App\Services\Notify\Notifier;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

/** Menjalankan satu pencarian dari bot Telegram, lalu mengirim lima hasil terbaik ke chat. Dipanggil setelah respons webhook terkirim. */
class RunTelegramSearch
{
    use Dispatchable;

    public function __construct(public string $chat, public array $params)
    {
    }

    public function handle(JobFinder $finder, Notifier $notifier): void
    {
        set_time_limit(240);
        // Di Telegram cukup baca sedikit deskripsi LinkedIn supaya hasil keluar lebih cepat.
        config(['platform.jobs.max_detail' => 10]);

        $links = array_map(fn ($l) => ['🔗 Cari juga di '.$l[0], $l[1]], JobFinder::searchLinks($this->params['positions'], $this->params['locations'], $this->params['kind']));
        $web = [['🌐 Buka halaman Lowongan', route('jobs.index')]];

        try {
            $warnings = [];
            $min = (int) config('platform.jobs.min_match', 25);
            $all = collect($finder->run($this->params, null, $warnings))
                ->filter(fn ($j) => (int) ($j['match'] ?? 0) >= $min)
                ->sortByDesc('match')->values();
            $results = $all->take(5);

            if ($results->isEmpty()) {
                $notifier->telegram($this->chat, "Belum ada lowongan yang cocok dalam 7 hari terakhir. Coba posisi atau lokasi lain, atau cari langsung di situs lain lewat tombol di bawah.\n\nPencarian baru: /cari, /magang, /skill.", array_merge($links, $web));

                return;
            }

            $kind = $this->params['kind'] === 'magang' ? 'magang' : 'lowongan';
            $topic = implode(', ', array_slice($this->params['positions'], 0, 3));
            $header = "<b>🦉 {$results->count()} {$kind} terbaik</b>".($all->count() > 5 ? " <i>dari {$all->count()} yang cocok</i>" : '')."\n<i>".JobCard::e($topic).' · '.JobCard::e(implode(', ', $this->params['locations'])).'</i>';

            // Sisanya (maks 50) dibuka di Google Sheet; tombol hanya muncul bila ada lebih dari 5 dan Apps Script sudah diatur.
            $sheet = $all->count() > 5 ? SheetExport::make($all->take(50), $topic) : null;
            $sheetBtn = $sheet ? [['📊 Lihat semua '.min($all->count(), 50).' hasil (Google Sheet)', $sheet]] : [];

            $notifier->telegram($this->chat, JobCard::render($results, $header)."\n\n<i>Cocok = kecocokan dengan keahlianmu. Cari lagi: /cari · /magang · /skill</i>", array_merge($sheetBtn, $links, $web), 'HTML');
        } catch (\Throwable $e) {
            report($e);
            $notifier->telegram($this->chat, 'Pencarian gagal. Coba lagi sebentar lagi.');
        } finally {
            Cache::decrement('jobsearch:running');
        }
    }
}
