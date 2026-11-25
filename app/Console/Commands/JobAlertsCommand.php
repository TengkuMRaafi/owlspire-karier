<?php

namespace App\Console\Commands;

use App\Models\JobAlert;
use App\Services\Jobs\JobFinder;
use App\Services\Notify\Notifier;
use Illuminate\Console\Command;

class JobAlertsCommand extends Command
{
    protected $signature = 'jobs:alerts {--limit=50 : Jumlah alert maksimal per jalan}';

    protected $description = 'Cari lowongan 24 jam terakhir untuk tiap alert aktif lalu kirim ringkasan ke email atau Telegram';

    public function handle(JobFinder $finder, Notifier $notifier): int
    {
        set_time_limit(0);
        $min = (int) config('platform.jobs.min_match', 25);
        $sent = 0;

        $alerts = JobAlert::with('user')->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('last_sent_at')->orWhere('last_sent_at', '<', now()->subHours(20)))
            ->orderBy('last_sent_at')->limit((int) $this->option('limit'))->get();

        foreach ($alerts as $alert) {
            if (! $alert->user) {
                continue;
            }

            try {
                $keywords = array_values((array) $alert->keywords);
                $available = $finder->availableKeys();
                $params = [
                    'positions' => array_slice($keywords, 0, 5), 'skills' => $keywords,
                    'locations' => $alert->locations ?: ['Indonesia'], 'hours' => 24,
                    'kind' => $alert->kind === 'magang' ? 'magang' : 'kerja',
                    'sources' => array_values(array_intersect(['linkedin'], $available)) ?: array_slice($available, 0, 1),
                ];
                $warnings = [];
                $results = collect($finder->run($params, null, $warnings))
                    ->filter(fn ($j) => (int) ($j['match'] ?? 0) >= $min)
                    ->sortByDesc('match')->take(5)->values();

                // Selalu tandai sudah diproses, supaya alert yang kosong tidak diulang tiap kali.
                $alert->update(['last_sent_at' => now()]);
                if ($results->isEmpty()) {
                    continue;
                }

                $lines = $results->map(fn ($j, $i) => ($i + 1).'. '.($j['title'] ?? '-').' - '.($j['company'] ?? '-').' ('.(int) ($j['match'] ?? 0)."%)\n   ".($j['url'] ?? ''))->all();
                $text = "Lowongan baru untuk \"".implode(', ', array_slice((array) $alert->keywords, 0, 3))."\":\n\n".implode("\n", $lines)."\n\nKelola alert: ".route('account.applications');

                if ($notifier->toUser($alert->user, $alert->channel, 'Lowongan baru untukmu', $text)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info('Alert diproses: '.$alerts->count().", terkirim: {$sent}");

        return self::SUCCESS;
    }
}
