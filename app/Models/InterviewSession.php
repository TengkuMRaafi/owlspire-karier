<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    protected $table = 'interview_sessions';

    protected $guarded = [];

    protected $casts = ['questions' => 'array', 'answers' => 'array', 'transcript' => 'array', 'report' => 'array'];

    /** Sesi lama (sebelum wawancara berbasis percakapan) memakai questions dan answers, bukan transcript. */
    public function isLegacy(): bool
    {
        return $this->transcript === null;
    }

    /**
     * Sesi lama yang masih berjalan diubah menjadi transkrip supaya bisa dilanjutkan dengan cara baru.
     * Tanggapan lama digabung ke pertanyaan berikutnya.
     */
    public function ensureTranscript(): void
    {
        if (! $this->isLegacy() || $this->status !== 'running') {
            return;
        }

        $questions = array_values($this->questions ?? []);
        $answers = array_values($this->answers ?? []);
        $turns = [];
        foreach ($questions as $i => $q) {
            if ($i > count($answers)) {
                break;
            }
            $prefix = $i > 0 && ! empty($answers[$i - 1]['reaction']) ? $answers[$i - 1]['reaction'].' ' : '';
            $turns[] = ['role' => 'interviewer', 'text' => $prefix.$q];
            if (isset($answers[$i])) {
                $turns[] = ['role' => 'candidate', 'text' => (string) ($answers[$i]['answer'] ?? '')];
            }
        }
        $this->update(['transcript' => $turns]);
    }

    /** @return array<int,array{role:string,text:string}> */
    public function turns(): array
    {
        return $this->transcript ?? [];
    }

    public function answerCount(): int
    {
        if ($this->isLegacy()) {
            return count($this->answers ?? []);
        }

        return count(array_filter($this->turns(), fn ($t) => ($t['role'] ?? '') === 'candidate'));
    }

    /** Pasangan pertanyaan dan jawaban, berurutan: tiap jawaban kandidat dengan ucapan pewawancara sebelumnya. */
    public function pairs(): array
    {
        $out = [];
        $q = '';
        foreach ($this->turns() as $t) {
            if (($t['role'] ?? '') === 'interviewer') {
                $q = (string) ($t['text'] ?? '');
            } else {
                $out[] = ['question' => $q, 'answer' => (string) ($t['text'] ?? '')];
            }
        }

        return $out;
    }
}
