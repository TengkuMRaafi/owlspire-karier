<?php

namespace App\Services\Careers;

/** Mencocokkan CV dengan satu teks lowongan: skill yang diminta, kata kunci yang belum ada, dan skor kecocokan. */
class CvJobMatcher
{
    private const STOP = ['yang', 'dan', 'atau', 'dengan', 'untuk', 'pada', 'dari', 'dalam', 'akan', 'serta', 'oleh', 'juga', 'dapat', 'agar', 'bagi', 'kami', 'anda', 'kamu', 'kita', 'memiliki', 'minimal', 'maksimal', 'diutamakan', 'lowongan', 'kualifikasi', 'persyaratan', 'tanggung', 'jawab', 'posisi', 'perusahaan', 'karyawan', 'bekerja', 'mampu', 'baik', 'lebih', 'sebagai', 'tahun', 'pengalaman', 'menguasai', 'memahami', 'the', 'and', 'for', 'with', 'you', 'your', 'our', 'will', 'are', 'have', 'has', 'this', 'that', 'from', 'work', 'working', 'experience', 'years', 'ability', 'skills', 'strong', 'team', 'role', 'job', 'must', 'etc', 'such', 'other', 'including', 'about', 'knowledge', 'good', 'excellent', 'plus', 'preferred', 'required', 'requirements'];

    public function __construct(private CvAnalyzer $analyzer)
    {
    }

    /** @return array{score:int,matched_skills:string[],missing_skills:string[],missing_keywords:string[],job_skill_count:int,verdict:string} */
    public function match(string $cvText, string $jobText): array
    {
        $cvSkills = collect($this->analyzer->detectSkills($cvText))->pluck('name');
        $jobSkills = collect($this->analyzer->detectSkills($jobText))->pluck('name');

        $matched = $jobSkills->intersect($cvSkills)->values()->all();
        $missingSkills = $jobSkills->diff($cvSkills)->values()->all();

        $keywords = $this->keywords($jobText, $jobSkills->map(fn ($s) => mb_strtolower($s))->all());
        $cvLow = mb_strtolower($cvText);
        $missingKw = array_values(array_filter($keywords, fn ($k) => ! preg_match('/(?<![\p{L}\p{N}])'.preg_quote($k, '/').'(?![\p{L}\p{N}])/u', $cvLow)));
        $kwCoverage = $keywords ? (count($keywords) - count($missingKw)) / count($keywords) : 0;

        $skillCoverage = $jobSkills->isNotEmpty() ? count($matched) / $jobSkills->count() : null;
        $score = $skillCoverage === null ? $kwCoverage * 100 : ($skillCoverage * 65 + $kwCoverage * 35);
        $score = (int) round(max(0, min(100, $score)));

        return [
            'score' => $score,
            'matched_skills' => $matched,
            'missing_skills' => $missingSkills,
            'missing_keywords' => array_slice($missingKw, 0, 12),
            'job_skill_count' => $jobSkills->count(),
            'verdict' => match (true) {
                $score >= 75 => 'Sangat cocok. Sesuaikan sedikit kata kunci lalu kirim.',
                $score >= 50 => 'Cukup cocok. Tambahkan skill dan kata kunci yang kurang bila memang kamu kuasai.',
                default => 'Belum cocok. Pertimbangkan menutup celah skill dulu atau sesuaikan CV dengan lowongan ini.',
            },
        ];
    }

    /** Kata yang paling sering muncul di lowongan (selain kata umum dan nama skill), sebagai kandidat kata kunci ATS. */
    private function keywords(string $job, array $skipSkillsLower): array
    {
        preg_match_all('/[\p{L}][\p{L}\p{N}+#.\-]{3,}/u', mb_strtolower($job), $m);
        $freq = array_count_values($m[0]);
        $freq = array_filter($freq, fn ($n, $w) => ! in_array($w, self::STOP, true) && ! in_array($w, $skipSkillsLower, true) && ! preg_match('/^\d/', $w), ARRAY_FILTER_USE_BOTH);
        arsort($freq);

        return array_slice(array_keys($freq), 0, 25);
    }
}
