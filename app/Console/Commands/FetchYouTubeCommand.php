<?php

namespace App\Console\Commands;

use App\Models\Career;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\Learning\YouTubeCcClient;
use Illuminate\Console\Command;

class FetchYouTubeCommand extends Command
{
    protected $signature = 'belajar:ambil-youtube {--karier= : slug karier (kosong = semua)} {--per=2 : video per topik} {--setujui : langsung tayang tanpa tinjauan admin} {--semua-lisensi : semua video yang boleh disematkan, bukan hanya Creative Commons} {--pasang : pasang video teratas langsung ke topik jalur yang belum punya video}';

    protected $description = 'Mencari video YouTube untuk tiap topik jalur belajar (bawaan: CC saja, menunggu persetujuan admin). Pakai --semua-lisensi --pasang untuk mengisi topik langsung.';

    public function handle(YouTubeCcClient $yt): int
    {
        if (! $yt->configured()) {
            $this->error('Isi YOUTUBE_API_KEY di .env dulu (YouTube Data API v3).');

            return self::FAILURE;
        }

        $careers = Career::listed()->when($this->option('karier'), fn ($q, $s) => $q->where('slug', $s))->with('course')->get();
        $added = 0;

        foreach ($careers as $career) {
            if (! $career->course) {
                continue;
            }
            $ccOnly = ! $this->option('semua-lisensi');
            if ($this->option('pasang')) {
                $added += $this->attachToTopics($yt, $career, $ccOnly);
                $this->line("{$career->name}: selesai");

                continue;
            }

            $section = CourseSection::firstOrCreate(['course_id' => $career->course->id, 'title' => 'Video rekomendasi'], ['position' => 2]);

            foreach ($career->detail('path', []) as [$title, $query]) {
                try {
                    $videos = $yt->search($query, (int) $this->option('per'), $ccOnly);
                } catch (\Throwable $e) {
                    $this->warn("{$career->slug}: {$e->getMessage()}");

                    continue 2;
                }

                foreach ($videos as $v) {
                    if (Lesson::where('video_id', $v['video_id'])->whereIn('section_id', $career->course->sections()->pluck('id'))->exists()) {
                        continue;
                    }
                    Lesson::create([
                        'section_id' => $section->id, 'title' => $v['title'], 'slug' => 'yt-'.$v['video_id'], 'type' => 'video',
                        'provider' => 'youtube', 'video_id' => $v['video_id'], 'content_url' => $v['url'], 'source_url' => $v['url'],
                        'license' => $v['license'], 'credit_name' => $v['channel'], 'duration_minutes' => $v['minutes'],
                        'body' => 'Topik: '.$title, 'position' => 100 + $added, 'is_free_preview' => true,
                        'is_approved' => (bool) $this->option('setujui'),
                    ]);
                    $added++;
                }
            }
            $this->line("{$career->name}: selesai");
        }

        $this->info("{$added} video baru ditambahkan".($this->option('setujui') ? '.' : ' (menunggu persetujuan di /admin/belajar).'));

        return self::SUCCESS;
    }

    /** Memasang video teratas ke topik-N yang masih berupa tautan pencarian. */
    private function attachToTopics(YouTubeCcClient $yt, Career $career, bool $ccOnly): int
    {
        $ids = $career->course->sections()->pluck('id');
        $n = 0;

        foreach ($career->detail('path', []) as $i => [$title, $query]) {
            $lesson = Lesson::whereIn('section_id', $ids)->where('slug', 'topik-'.($i + 1))->first();
            if (! $lesson || $lesson->provider === 'youtube') {
                continue;
            }
            try {
                $v = $yt->search($query, 1, $ccOnly)[0] ?? null;
            } catch (\Throwable $e) {
                $this->warn("{$career->slug}: {$e->getMessage()}");

                return $n;
            }
            if (! $v) {
                continue;
            }
            $lesson->update([
                'type' => 'video', 'provider' => 'youtube', 'video_id' => $v['video_id'], 'video_title' => $v['title'],
                'content_url' => $v['url'], 'source_url' => $v['url'], 'credit_name' => $v['channel'] ?: 'YouTube',
                'license' => $v['license'], 'duration_minutes' => $v['minutes'], 'is_approved' => true,
            ]);
            $n++;
        }

        return $n;
    }
}
