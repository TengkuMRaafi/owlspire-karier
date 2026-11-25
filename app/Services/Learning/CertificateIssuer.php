<?php

namespace App\Services\Learning;

use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Support\Str;

class CertificateIssuer
{
    public function issue(int $userId, Course $course): Certificate
    {
        return Certificate::firstOrCreate(
            ['user_id' => $userId, 'course_id' => $course->id],
            ['code' => strtoupper(Str::random(10)), 'title' => 'Sertifikat: ' . $course->title, 'issued_at' => now()],
        );
    }
}
