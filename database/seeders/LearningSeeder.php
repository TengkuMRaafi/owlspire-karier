<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

/** Membuat satu jalur belajar per karier dari "path" di data karier. */
class LearningSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Career::with('skills')->orderBy('rank')->get() as $career) {
            $course = Course::firstOrCreate(['slug' => 'jalur-'.$career->slug], [
                'title' => 'Jalur belajar: '.$career->name,
                'summary' => 'Empat topik berurutan untuk mulai menekuni '.$career->name.'. '.$career->summary,
                'level' => 'pemula', 'category' => $career->division,
                'is_published' => true, 'is_premium' => false, 'career_id' => $career->id,
            ]);
            $course->skills()->syncWithoutDetaching($career->skills->pluck('id'));

            $section = CourseSection::firstOrCreate(['course_id' => $course->id, 'title' => 'Jalur inti'], ['position' => 1]);

            foreach ($career->detail('path', []) as $i => [$title, $query, $goal]) {
                Lesson::firstOrCreate(['section_id' => $section->id, 'slug' => 'topik-'.($i + 1)], [
                    'title' => $title,
                    'type' => 'artikel',
                    'provider' => 'link',
                    'content_url' => Lesson::searchUrl($query),
                    'source_url' => Lesson::searchUrl($query),
                    'license' => 'Pencarian video di YouTube',
                    'body' => $goal,
                    'position' => $i + 1,
                    'is_free_preview' => $i < 2, // dua topik pertama gratis, sisanya Premium
                    'is_approved' => true,
                ]);
            }
        }

        \App\Services\Learning\JalurResmiSeeder::apply();
        \App\Services\Learning\VideoSeeder::apply();
        \App\Services\Learning\MateriSeeder::apply();
    }
}
