<?php

namespace App\Services\Jobs;

/** Persentase kecocokan lowongan dengan skill dan posisi pengguna (port dari score_job job_finder). */
class JobScorer
{
    public function __construct(private Divisions $divisions)
    {
    }

    /** 60% skill yang muncul (+ setengah poin istilah khas bidang), 35% kedekatan judul; judul tak terkait dikurangi. */
    public function score(array $job, array $skills, array $ctx): array
    {
        $title = mb_strtolower($job['title']);
        $desc = (string) ($job['description'] ?? '');
        $text = $title.' '.mb_strtolower($desc);

        $matched = array_values(array_filter($skills, fn ($s) => preg_match('/(?<![a-z0-9])'.preg_quote(mb_strtolower($s), '/').'(?![a-z0-9])/u', $text)));
        $extra = $this->divisions->related($text, $ctx, $skills);
        $base = max(3, min(count($skills), 8));
        $skillPts = min(100, 100 * (count($matched) + 0.5 * min(count($extra), 4)) / $base);
        $titlePts = $this->divisions->titlePoints($title, $ctx);
        $pct = 0.6 * $skillPts + $titlePts;
        if ($titlePts === 0 && $ctx['divs']) {
            $pct *= 0.6;
        }

        $demanded = CvProfileExtractor::bankSkills($text);
        $job['match'] = (int) min(100, round($pct));
        $job['matched'] = $matched;
        $job['related'] = array_slice($extra, 0, 6);
        $job['division'] = $this->divisions->classify($title, $text, $ctx['label']);
        $job['demanded'] = array_values(array_unique(array_merge($demanded, $this->divisions->demanded($text))));
        $job['snippet'] = mb_strlen($desc) > 320 ? mb_substr($desc, 0, 320).'...' : $desc;
        unset($job['description']);

        return $job;
    }
}
