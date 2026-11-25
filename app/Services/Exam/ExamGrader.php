<?php

namespace App\Services\Exam;

use App\Models\ExamAttempt;

/** Penilaian dilakukan di server. */
class ExamGrader
{
    public function grade(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->score !== null && $attempt->finished_at !== null) {
            return $attempt; // sudah dinilai; aman dipanggil dua kali
        }

        $attempt->loadMissing('answers.question.segment', 'type');
        $points = (int) config('platform.exam.points_per_correct', 5);

        $perSegment = [];
        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            if (! $question) {
                continue;
            }

            $code = $question->segment->code ?? 'umum';
            $perSegment[$code] ??= [
                'nama' => $question->segment->name ?? $code,
                'benar' => 0, 'salah' => 0, 'kosong' => 0, 'total' => 0, 'poin' => 0, 'maks' => 0,
            ];

            $answered = $answer->selected !== null && $answer->selected !== '';
            $correct = $answered && strcasecmp($answer->selected, $question->answer) === 0;
            $answer->update(['is_correct' => $answered ? $correct : null]);

            $perSegment[$code]['total']++;
            $perSegment[$code]['maks'] += $points;
            if (! $answered) {
                $perSegment[$code]['kosong']++;
            } elseif ($correct) {
                $perSegment[$code]['benar']++;
                $perSegment[$code]['poin'] += $points;
            } else {
                $perSegment[$code]['salah']++;
            }
        }

        $attempt->update([
            'score' => $perSegment,
            'total_score' => array_sum(array_column($perSegment, 'poin')),
            'passed' => $this->passed($attempt, $perSegment),
            'finished_at' => $attempt->finished_at ?? now(),
        ]);

        return $attempt;
    }

    private function passed(ExamAttempt $attempt, array $perSegment): ?bool
    {
        if ($attempt->mode !== 'full') {
            return null; // mode coba tidak diberi status lulus
        }

        $rules = $attempt->type->passing_rules ?? [];

        if (! empty($rules['segments'])) {
            foreach ($rules['segments'] as $code => $minPercent) {
                $seg = $perSegment[$code] ?? null;
                if (! $seg || $seg['maks'] === 0 || ($seg['poin'] / $seg['maks'] * 100) < (float) $minPercent) {
                    return false;
                }
            }

            return true;
        }

        $max = array_sum(array_column($perSegment, 'maks'));
        $total = array_sum(array_column($perSegment, 'poin'));

        return $max > 0 && ($total / $max * 100) >= (float) ($rules['min_percent'] ?? 60);
    }
}
