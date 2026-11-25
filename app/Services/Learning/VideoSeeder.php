<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\Lesson;

/** Memasang video dari database/seeders/data/belajar-video.json ke topik jalur belajar (topik-1, topik-2, ...). */
class VideoSeeder
{
    public static function apply(): int
    {
        $file = database_path('seeders/data/belajar-video.json');
        if (! is_file($file)) {
            return 0;
        }

        $n = 0;
        foreach ((array) json_decode((string) file_get_contents($file), true) as $v) {
            $course = Course::where('slug', 'jalur-'.$v['career'])->first();
            if (! $course) {
                continue;
            }
            $lesson = Lesson::whereIn('section_id', $course->sections()->pluck('id'))->where('slug', 'topik-'.$v['topic'])->first();
            if (! $lesson || $lesson->provider === 'youtube') {
                continue;
            }
            $url = 'https://www.youtube.com/watch?v='.$v['video_id'];
            $lesson->update([
                'type' => 'video', 'provider' => 'youtube', 'video_id' => $v['video_id'], 'video_title' => $v['title'],
                'content_url' => $url, 'source_url' => $url, 'credit_name' => 'Kanal pembuat di YouTube',
                'license' => 'Disematkan lewat pemutar resmi YouTube', 'is_approved' => true,
            ]);
            $n++;
        }

        return $n;
    }
}
