<?php

namespace App\Services\Careers;

use App\Models\JobListing;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

/** Perekat antar modul (skill gap -> rekomendasi materi). */
class CareerService
{
    /** @return array{missing: \Illuminate\Support\Collection, courses: \Illuminate\Support\Collection} */
    public function skillGap(int $userId, JobListing $job): array
    {
        $have = DB::table('user_skills')->where('user_id', $userId)->pluck('skill_id');
        $missing = $job->skills()->whereNotIn('skills.id', $have)->get();

        $courses = Course::query()
            ->where('is_published', true)
            ->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $missing->pluck('id')))
            ->get();

        return ['missing' => $missing, 'courses' => $courses];
    }
}
