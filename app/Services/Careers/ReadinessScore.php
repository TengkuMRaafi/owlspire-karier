<?php

namespace App\Services\Careers;

use App\Models\User;
use App\Models\Career;
use App\Models\ExamAttempt;
use App\Models\CvProfile;
use App\Models\LessonProgress;

/** Skor kesiapan 0-100 untuk satu karier target. */
class ReadinessScore
{
    /** @return array{total:int, parts:array<string,array{label:string,value:int,weight:int,hint:string}>} */
    public function for(User $user, Career $career): array
    {
        $best = ExamAttempt::where('user_id', $user->id)->where('exam_type_id', $career->exam_type_id)
            ->whereNotNull('finished_at')->get()
            ->map(function ($a) {
                $max = array_sum(array_column($a->score ?? [], 'maks'));

                return $max > 0 ? (int) round($a->total_score / $max * 100) : 0;
            })->max() ?? 0;

        $cv = 0;
        $profile = CvProfile::where('user_id', $user->id)->first();
        if ($profile) {
            $cv = (int) (collect($profile->parsed['matches'] ?? [])->firstWhere('career_id', $career->id)['score'] ?? 0);
        }

        $learn = 0;
        if ($career->course) {
            $ids = $career->course->lessons()->where('is_approved', true)->pluck('lessons.id');
            if ($ids->isNotEmpty()) {
                $done = LessonProgress::where('user_id', $user->id)->whereIn('lesson_id', $ids)->whereNotNull('completed_at')->count();
                $learn = (int) round($done / $ids->count() * 100);
            }
        }

        $parts = [
            'exam' => ['label' => 'Simulasi', 'value' => $best, 'weight' => 50, 'hint' => 'Skor terbaikmu di simulasi karier ini'],
            'cv' => ['label' => 'Kecocokan CV', 'value' => $cv, 'weight' => 25, 'hint' => 'Skill di CV yang cocok dengan syarat karier (Premium)'],
            'learn' => ['label' => 'Belajar', 'value' => $learn, 'weight' => 25, 'hint' => 'Topik jalur belajar yang sudah selesai'],
        ];
        $total = (int) round(collect($parts)->sum(fn ($p) => $p['value'] * $p['weight'] / 100));

        return ['total' => $total, 'parts' => $parts];
    }
}
