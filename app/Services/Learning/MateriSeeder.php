<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\Lesson;

/**
 * Memasang deskripsi lengkap jalur dan isi tiap topik dari database/seeders/data/belajar-materi.json.
 * Aman diulang. Teks yang sudah diubah admin (lebih panjang dari bawaan) tidak ditimpa.
 */
class MateriSeeder
{
    /** Ringkasan bawaan LearningSeeder hanya satu kalimat pendek; di atas batas ini dianggap sudah disunting. */
    private const SEED_BODY_MAX = 160;

    public static function apply(): int
    {
        $file = database_path('seeders/data/belajar-materi.json');
        if (! is_file($file)) {
            return 0;
        }

        $n = 0;
        foreach ((array) json_decode((string) file_get_contents($file), true) as $slug => $c) {
            $course = Course::where('slug', 'jalur-'.$slug)->first();
            if (! $course) {
                continue;
            }

            if (blank($course->description) && ! empty($c['description'])) {
                $course->update(['description' => $c['description']]);
            }

            $sectionIds = $course->sections()->pluck('id');
            foreach ((array) ($c['topics'] ?? []) as $t) {
                $lesson = Lesson::whereIn('section_id', $sectionIds)->where('slug', 'topik-'.$t['topic'])->first();
                if (! $lesson) {
                    continue;
                }

                $attrs = [];
                if (blank($lesson->body) || mb_strlen((string) $lesson->body) < self::SEED_BODY_MAX) {
                    $attrs['body'] = $t['overview'];
                }
                if (empty($lesson->details)) {
                    $attrs['details'] = [
                        'points' => $t['points'] ?? [],
                        'terms' => $t['terms'] ?? [],
                        'practice' => $t['practice'] ?? null,
                    ];
                }
                if ($attrs) {
                    $lesson->update($attrs);
                    $n++;
                }
            }
        }

        return $n + self::applyDeep();
    }

    /**
     * Penjelasan mendalam per topik: database/seeders/data/belajar-mendalam/<slug-karier>.json
     * (topics.<nomor>). Disimpan di lessons.details['deep']. Naikkan angka "v" di berkas untuk
     * memperbarui isi yang sudah terpasang; tanpa kenaikan "v" isi yang ada tidak ditimpa.
     */
    public static function applyDeep(): int
    {
        $n = 0;
        foreach (glob(database_path('seeders/data/belajar-mendalam/*.json')) ?: [] as $file) {
            $course = Course::where('slug', 'jalur-'.basename($file, '.json'))->first();
            if (! $course) {
                continue;
            }

            $sectionIds = $course->sections()->pluck('id');
            foreach ((array) (json_decode((string) file_get_contents($file), true)['topics'] ?? []) as $no => $deep) {
                $lesson = Lesson::whereIn('section_id', $sectionIds)->where('slug', 'topik-'.$no)->first();
                if (! $lesson) {
                    continue;
                }

                $details = $lesson->details ?? [];
                if (($details['deep']['v'] ?? 0) >= ($deep['v'] ?? 1)) {
                    continue;
                }

                $details['deep'] = $deep;
                $lesson->update(['details' => $details]);
                $n++;
            }
        }

        return $n;
    }
}
