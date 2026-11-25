<?php

namespace App\Services\Exam;

use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamType;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Siklus satu percobaan ujian: mulai, simpan jawaban, selesai. */
class ExamSession
{
    public function __construct(
        private ExamGrader $grader,
        private EntitlementService $entitlements,
    ) {
    }

    /**
     * @throws \DomainException bila tidak boleh memulai (mis. belum punya tiket) atau soal belum tersedia.
     */
    public function start(ExamType $type, string $mode, ?User $user, ?string $guestToken): ExamAttempt
    {
        if (! $type->is_active) {
            throw new \DomainException('Jenis ujian ini sedang dinonaktifkan.');
        }
        if (! in_array($mode, ['trial', 'full'], true)) {
            throw new \DomainException('Mode ujian tidak dikenal.');
        }
        if ($mode === 'full' && ! $user) {
            throw new \DomainException('Masuk atau daftar dulu untuk memakai tiket premium.');
        }
        if ($mode === 'trial' && ! $user && ! $guestToken) {
            throw new \DomainException('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
        }

        $questions = $this->pickQuestions($type, $mode, $user);
        if ($questions->isEmpty()) {
            throw new \DomainException('Soal untuk ujian ini belum tersedia.');
        }

        return DB::transaction(function () use ($type, $mode, $user, $guestToken, $questions) {
            // Jatah tiket dipakai di dalam transaksi yang sama dengan pembuatan percobaan (gagal membuat percobaan = jatah kembali).
            if ($mode === 'full' && ! $this->entitlements->useForFullAttempt($user->id, $type->id)) {
                throw new \DomainException('Jatah tiket untuk simulasi ini sudah habis, jadi ujian penuhnya terkunci lagi. Beli tiket baru di halaman Harga untuk lanjut.');
            }

            $duration = $mode === 'full' ? $type->duration_seconds : $type->trial_duration_seconds;

            $attempt = ExamAttempt::create([
                'user_id' => $user?->id,
                'guest_token' => $user ? null : $guestToken,
                'exam_type_id' => $type->id,
                'mode' => $mode,
                'user_agent' => $this->agent(),
                'started_at' => now(),
                'deadline_at' => now()->addSeconds((int) $duration),
            ]);

            // Urutan baris = urutan soal di ruang ujian (segmen berurutan, soal diacak di dalam segmen).
            foreach ($questions as $question) {
                ExamAttemptAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $question->id]);
            }

            return $attempt;
        });
    }

    /**
     * Ulangi simulasi: percobaan baru dengan soal yang sama persis dengan percobaan sebelumnya.
     * Mode dan durasi mengikuti percobaan asal. Urutan segmen tetap, soal di dalam segmen diacak ulang.
     * Ujian penuh tetap memakai 1 jatah tiket, sama seperti memulai ujian penuh biasa.
     *
     * @throws \DomainException bila tidak boleh mengulang (mis. tiket habis) atau soalnya sudah tidak ada.
     */
    public function retake(ExamAttempt $previous, ?User $user, ?string $guestToken): ExamAttempt
    {
        $type = $previous->type;
        $mode = $previous->mode;

        if (! $previous->isFinished()) {
            throw new \DomainException('Selesaikan simulasi ini dulu sebelum mengulangnya.');
        }
        if (! $type || ! $type->is_active) {
            throw new \DomainException('Jenis ujian ini sedang dinonaktifkan.');
        }
        if ($mode === 'full' && ! $user) {
            throw new \DomainException('Masuk atau daftar dulu untuk memakai tiket premium.');
        }
        if ($mode === 'trial' && ! $user && ! $guestToken) {
            throw new \DomainException('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
        }

        // Soal yang sudah dihapus dari bank soal dilewati; urutan segmen mengikuti percobaan asal.
        $rows = $previous->answers()->with('question')->get()->filter(fn ($a) => $a->question !== null);
        $questions = $rows
            ->groupBy(fn ($a) => $a->question->segment_id)
            ->flatMap(fn ($group) => $group->shuffle()->pluck('question'))
            ->values();

        if ($questions->isEmpty()) {
            throw new \DomainException('Soal simulasi ini sudah tidak tersedia.');
        }

        return DB::transaction(function () use ($type, $mode, $user, $guestToken, $questions, $previous) {
            if ($mode === 'full' && ! $this->entitlements->useForFullAttempt($user->id, $type->id)) {
                throw new \DomainException('Jatah tiket untuk simulasi ini sudah habis, jadi ujian penuhnya terkunci lagi. Beli tiket baru di halaman Harga untuk lanjut.');
            }

            $duration = $mode === 'full' ? $type->duration_seconds : $type->trial_duration_seconds;

            $attempt = ExamAttempt::create([
                'user_id' => $user?->id,
                'guest_token' => $user ? null : $guestToken,
                'exam_type_id' => $type->id,
                'mode' => $mode,
                'retake_of' => $previous->id,
                'user_agent' => $this->agent(),
                'started_at' => now(),
                'deadline_at' => now()->addSeconds((int) $duration),
            ]);

            foreach ($questions as $question) {
                ExamAttemptAnswer::create(['attempt_id' => $attempt->id, 'question_id' => $question->id]);
            }

            return $attempt;
        });
    }

    private function agent(): ?string
    {
        $ua = request()->userAgent();

        return $ua ? mb_substr($ua, 0, 250) : null;
    }

    /** @return array{finished:bool, remaining:int} */
    public function saveAnswer(ExamAttempt $attempt, int $questionId, ?string $selected, bool $flagged): array
    {
        if ($attempt->isFinished()) {
            return ['finished' => true, 'remaining' => 0];
        }

        $grace = (int) config('platform.exam.grace_seconds', 5);
        if ($attempt->deadline_at && now()->greaterThan($attempt->deadline_at->copy()->addSeconds($grace))) {
            $this->finish($attempt);

            return ['finished' => true, 'remaining' => 0];
        }

        $answer = ExamAttemptAnswer::where('attempt_id', $attempt->id)->where('question_id', $questionId)->first();
        if (! $answer) {
            throw new \InvalidArgumentException('Soal tidak termasuk dalam ujian ini.');
        }

        $selected = $selected !== null ? strtoupper(trim($selected)) : null;
        if ($selected !== null && $selected !== '') {
            $answer->loadMissing('question');
            $valid = array_keys($answer->question->options ?? []);
            if (! in_array($selected, $valid, true)) {
                throw new \InvalidArgumentException('Pilihan jawaban tidak valid.');
            }
        } else {
            $selected = null;
        }

        // Jejak aktivitas: kapan pertama kali dijawab dan berapa kali jawabannya diganti.
        $changed = $selected !== null && $answer->selected !== null && $answer->selected !== $selected;
        $answer->update([
            'selected' => $selected,
            'flagged' => $flagged,
            'answered_at' => $selected !== null ? now() : null,
            'first_answered_at' => $answer->first_answered_at ?? ($selected !== null ? now() : null),
            'changes' => (int) $answer->changes + ($changed ? 1 : 0),
        ]);

        return ['finished' => false, 'remaining' => $attempt->remainingSeconds()];
    }

    public function finish(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->isFinished()) {
            return $attempt;
        }

        // Waktu selesai tidak boleh melewati batas resmi, walau penggunanya baru membuka halaman setelah itu.
        $attempt->finished_at = $attempt->deadline_at && now()->greaterThan($attempt->deadline_at) ? $attempt->deadline_at : now();
        $this->grader->grade($attempt);
        if ($attempt->user_id) {
            app(\App\Services\Study\StudyActivity::class)->record((int) $attempt->user_id, 'exam');
        }

        return $attempt->refresh();
    }

    /**
     * Soal diambil acak dari stok tiap segmen sebanyak jumlah ujian penuh (mis. stok 500, ujian 100 soal: 100 diambil acak).
     * Urutan prioritas: soal yang sesuai mode dan belum pernah dikerjakan pengguna, lalu yang sudah pernah,
     * lalu sisanya. Di dalam tiap tingkat, soal disebar merata menurut topik supaya ujian tidak menumpuk di satu bahasan.
     *
     * @return Collection<int, Question>
     */
    private function pickQuestions(ExamType $type, string $mode, ?User $user = null): Collection
    {
        $picked = collect();

        $seen = [];
        if ($user) {
            $seen = array_flip(
                ExamAttemptAnswer::query()
                    ->join('exam_attempts', 'exam_attempts.id', '=', 'exam_attempt_answers.attempt_id')
                    ->where('exam_attempts.user_id', $user->id)
                    ->where('exam_attempts.exam_type_id', $type->id)
                    ->pluck('exam_attempt_answers.question_id')
                    ->all()
            );
        }

        // Ujian penuh dibatasi total (bawaan 100 soal) dan diambil acak dari bank; kekurangan bank satu bagian digeser ke bagian lain.
        $fullLimits = [];
        if ($mode === 'full') {
            $counts = Question::whereIn('segment_id', $type->segments->pluck('id'))
                ->selectRaw('segment_id, count(*) as n')->groupBy('segment_id')->pluck('n', 'segment_id');
            $fullLimits = FullExamPlan::allocate(
                $type->segments->map(fn ($s) => (object) ['id' => $s->id, 'question_count_full' => $s->question_count_full, 'questions_count' => (int) ($counts[$s->id] ?? 0)])
            );
        }

        foreach ($type->segments as $segment) {
            $limit = $mode === 'full' ? ($fullLimits[$segment->id] ?? 0) : $segment->question_count_trial;
            $all = Question::where('segment_id', $segment->id)->get();

            // Mode coba mengutamakan soal bertanda "trial"; mode penuh mengutamakan sisanya.
            [$preferred, $rest] = $mode === 'trial'
                ? [$all->where('is_trial', true), $all->where('is_trial', false)]
                : [$all->where('is_trial', false), $all->where('is_trial', true)];

            $tiers = [
                $preferred->reject(fn ($q) => isset($seen[$q->id])),
                $preferred->filter(fn ($q) => isset($seen[$q->id])),
                $rest->reject(fn ($q) => isset($seen[$q->id])),
                $rest->filter(fn ($q) => isset($seen[$q->id])),
            ];

            $chosen = collect();
            foreach ($tiers as $tier) {
                if ($chosen->count() >= $limit) {
                    break;
                }
                $chosen = $chosen->concat($this->spreadByTopic($tier, $limit - $chosen->count()));
            }

            $picked = $picked->concat($chosen->shuffle());
        }

        return $picked->values();
    }

    /** Mengambil $take soal acak dari kumpulan, bergiliran antar topik (soal tanpa topik dianggap satu topik). */
    private function spreadByTopic(Collection $pool, int $take): Collection
    {
        if ($take <= 0 || $pool->isEmpty()) {
            return collect();
        }

        $groups = $pool->groupBy(fn ($q) => (string) ($q->topic ?? ''))->map(fn ($g) => $g->shuffle()->values())->shuffle()->values();

        $out = collect();
        for ($round = 0; $out->count() < $take; $round++) {
            $added = false;
            foreach ($groups as $group) {
                if (isset($group[$round])) {
                    $out->push($group[$round]);
                    $added = true;
                    if ($out->count() >= $take) {
                        break;
                    }
                }
            }
            if (! $added) {
                break;
            }
        }

        return $out;
    }
}
