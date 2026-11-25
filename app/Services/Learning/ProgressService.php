<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;

class ProgressService
{
    public function percent(int $userId, Course $course): int
    {
        $lessonIds = Lesson::whereIn('section_id', $course->sections()->pluck('id'))->where('is_approved', true)->pluck('id');
        if ($lessonIds->isEmpty()) {
            return 0;
        }

        $done = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        return (int) round($done / $lessonIds->count() * 100);
    }
}
