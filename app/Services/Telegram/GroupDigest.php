<?php

namespace App\Services\Telegram;

use App\Models\TelegramGroup;
use App\Services\Jobs\JobFinder;
use App\Services\Notify\Notifier;
use Illuminate\Support\Facades\Cache;

/** Menyusun dan mengirim info lowongan ke satu grup. Hasil pencarian yang sama dipakai bersama antar grup selama sejam. */
class GroupDigest
{
    public function __construct(private JobFinder $finder, private Notifier $notifier)
    {
    }

    /** @param bool $preview true = contoh dari /contoh: tidak memfilter yang sudah terkirim dan tidak mencatatnya */
    public function send(TelegramGroup $g, string $title, bool $preview = false): bool
    {
        $sent = $preview ? [] : (array) $g->sent_urls;
        $fresh = collect($this->results($g))->reject(fn ($j) => in_array($j['url'] ?? '', $sent, true))->take(5)->values();

        if ($fresh->isEmpty()) {
            if ($preview) {
                $this->notifier->telegram($g->chat_id, 'Belum ada lowongan yang cocok untuk topik grup ini. Atur dengan /topik, mis. /topik data analyst | excel | Jakarta');
            }

            return true; // tidak ada yang baru: lewati tanpa menandai gagal
        }

        $kind = $this->params($g)['kind'];
        $topic = implode(', ', array_slice($this->params($g)['positions'], 0, 3));
        $links = array_map(fn ($l) => ['🔗 Cari juga di '.$l[0], $l[1]], JobFinder::searchLinks($this->params($g)['positions'], $this->params($g)['locations'], $this->params($g)['kind']));
        $buttons = array_merge($links, [['🌐 Cari lebih banyak di OwlSpire.ID', route('jobs.index')]]);

        $header = '<b>'.JobCard::e($title)."</b>\n<i>".JobCard::e($topic).' · '.JobCard::e($kind).'</i>';
        $ok = $this->notifier->telegram($g->chat_id, JobCard::render($fresh, $header)."\n\n<i>Atur topik dan zona waktu: /jadwal</i>", $buttons, 'HTML');

        if ($preview) {
            return $ok;
        }
        if ($ok) {
            $g->update(['sent_urls' => array_slice(array_values(array_unique(array_merge($fresh->pluck('url')->all(), $sent))), 0, 40), 'fail_count' => 0]);
        } else {
            // Bot dikeluarkan atau tidak boleh mengirim: berhenti setelah tiga kali gagal berturut-turut.
            $g->increment('fail_count');
            if ($g->fail_count >= 3) {
                $g->update(['is_active' => false]);
            }
        }

        return $ok;
    }

    private function params(TelegramGroup $g): array
    {
        $positions = $g->positions ?: (array) config('platform.telegram.group_positions');

        return [
            'positions' => $positions, 'skills' => $g->skills ?: $positions,
            'locations' => $g->locations ?: ['Indonesia'], 'hours' => 24, 'kind' => $g->kind === 'magang' ? 'magang' : 'kerja',
            'sources' => $this->sources(),
        ];
    }

    private function results(TelegramGroup $g): array
    {
        $p = $this->params($g);
        if (! $p['positions'] || ! $p['sources']) {
            return [];
        }

        return Cache::remember('tgdigest:'.md5(json_encode($p)), 3300, function () use ($p) {
            set_time_limit(240);
            config(['platform.jobs.max_detail' => 10]);
            $warnings = [];
            $min = (int) config('platform.jobs.min_match', 25);

            return collect($this->finder->run($p, null, $warnings))
                ->filter(fn ($j) => (int) ($j['match'] ?? 0) >= $min)
                ->sortByDesc('match')->take(15)->values()->all();
        });
    }

    private function sources(): array
    {
        $default = array_values(array_map(fn ($s) => $s['id'], array_filter($this->finder->catalog(), fn ($s) => $s['default'] && $s['available'])));
        if ($default) {
            return $default;
        }

        return array_values(array_intersect(['linkedin'], $this->finder->availableKeys())) ?: array_slice($this->finder->availableKeys(), 0, 1);
    }
}
