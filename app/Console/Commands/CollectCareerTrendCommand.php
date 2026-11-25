<?php

namespace App\Console\Commands;

use App\Models\Career;
use App\Services\Careers\CareerDemandCollector;
use App\Services\Careers\CareerTrend;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CollectCareerTrendCommand extends Command
{
    protected $signature = 'karier:kumpulkan-tren {--dry-run : Tampilkan hasil tanpa menyimpan}';

    protected $description = 'Kumpulkan permintaan lowongan per karier dari API resmi, lalu simpan papan Top 5 minggu ini';

    public function handle(CareerDemandCollector $collector, CareerTrend $trend): int
    {
        $careers = Career::listed()->get();
        if ($careers->isEmpty()) {
            $this->warn('Belum ada karier aktif. Jalankan php artisan db:seed dulu.');

            return self::FAILURE;
        }

        $names = implode(', ', array_map(fn ($a) => $a->label(), $collector->adapters()));
        $this->info("Mengambil lowongan dari: {$names}");

        try {
            $res = $collector->collect($careers);
        } catch (Throwable $e) {
            // Arsip minggu lalu tidak ditimpa, jadi landing tetap menampilkan papan terakhir yang baik.
            Log::error('karier:kumpulkan-tren gagal: '.$e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($res['failed'] as $key => $msg) {
            $this->warn("Sumber {$key} gagal dan dilewati: ".mb_substr($msg, 0, 120));
            Log::warning("karier:kumpulkan-tren sumber {$key} gagal: {$msg}");
        }

        $rows = $trend->rebuild($res['counts'], null, ! $this->option('dry-run'));
        $this->table(['#', 'Karier', 'Lowongan', 'Simulasi', 'Target', 'Skor'], array_map(fn ($r) => [
            $r['position'], $r['career']->name, $r['jobs'], $r['sims'], $r['targets'], $r['score'],
        ], $rows));

        $this->line("Lowongan unik terbaca: {$res['pool']}, belum cocok ke karier mana pun: {$res['unmatched']}.");
        if ($this->getOutput()->isVerbose() && $res['unmatched_sample']) {
            $this->line('Contoh judul yang belum cocok: '.implode(' | ', $res['unmatched_sample']));
        }
        $this->info($this->option('dry-run') ? 'Dry run: tidak ada yang disimpan.' : 'Papan minggu ini tersimpan.');

        return self::SUCCESS;
    }
}
