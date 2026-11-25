<?php

namespace App\Services\Study;

use App\Models\ExamAttempt;
use App\Models\StudyProfile;
use Illuminate\Support\Carbon;

/**
 * Menyusun rencana belajar harian dari target ujian, tanggal ujian, hasil simulasi, dan antrean soal salah.
 * Murni aturan (tanpa LLM), jadi selalu tersedia dan hasilnya konsisten.
 */
class StudyPlanner
{
    public function __construct(private StudyActivity $activity)
    {
    }

    /**
     * @return array{days_left:?int, phase:string, weak:array<int,array{name:string,pct:int}>, has_attempts:bool,
     *               today:array<int,array>, week:array<int,array{date:string,label:string,tasks:array}>}
     */
    public function build(StudyProfile $profile): array
    {
        $userId = (int) $profile->user_id;
        $today = StudyActivity::today();
        // Selisih hari dari tanggal kalender (bukan jam), supaya zona waktu tidak menggeser hitungannya.
        $daysLeft = $profile->exam_date
            ? (int) Carbon::parse($today->toDateString(), 'UTC')->diffInDays(Carbon::parse($profile->exam_date->toDateString(), 'UTC'), false)
            : null;

        [$weak, $hasAttempts] = $this->weakSegments($profile);
        $counts = $this->activity->todayCounts($userId);

        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->addDays($i);
            $dl = $daysLeft === null ? null : $daysLeft - $i;
            if ($dl !== null && $dl < 0) {
                break;
            }
            $tasks = $this->tasksFor($profile, $i, $dl, $weak, $hasAttempts);
            if ($i === 0) {
                foreach ($tasks as &$t) {
                    $t['done'] = match ($t['key']) {
                        'retake', 'drill', 'full', 'baseline' => $counts['exams'] >= 1,
                        default => false,
                    };
                }
                unset($t);
            }
            $week[] = ['date' => $date->toDateString(), 'label' => $i === 0 ? 'Hari ini' : $date->translatedFormat('l, d M'), 'tasks' => $tasks];
        }

        return [
            'days_left' => $daysLeft,
            'phase' => $this->phase($daysLeft),
            'weak' => $weak,
            'has_attempts' => $hasAttempts,
            'today' => $week[0]['tasks'] ?? [],
            'week' => $week,
        ];
    }

    public function phase(?int $daysLeft): string
    {
        return match (true) {
            $daysLeft === null => 'Rutin',
            $daysLeft < 0 => 'Selesai',
            $daysLeft === 0 => 'Hari ujian',
            $daysLeft <= 3 => 'Hari-hari terakhir',
            $daysLeft <= 14 => 'Pemantapan',
            $daysLeft <= 30 => 'Penguatan',
            default => 'Fondasi',
        };
    }

    /** @return array{0: array<int,array{name:string,pct:int}>, 1: bool} segmen terlemah dulu, dan apakah sudah ada simulasi */
    private function weakSegments(StudyProfile $profile): array
    {
        $attempts = ExamAttempt::where('user_id', $profile->user_id)->whereNotNull('finished_at')
            ->when($profile->exam_type_id, fn ($q) => $q->where('exam_type_id', $profile->exam_type_id))
            ->latest('finished_at')->limit(5)->get(['id', 'score']);

        $agg = [];
        foreach ($attempts as $a) {
            foreach ((array) $a->score as $seg) {
                $name = (string) ($seg['nama'] ?? '');
                if ($name === '' || (int) ($seg['maks'] ?? 0) <= 0) {
                    continue;
                }
                $agg[$name]['poin'] = ($agg[$name]['poin'] ?? 0) + (int) $seg['poin'];
                $agg[$name]['maks'] = ($agg[$name]['maks'] ?? 0) + (int) $seg['maks'];
            }
        }

        $weak = collect($agg)->map(fn ($v, $name) => ['name' => $name, 'pct' => (int) round($v['poin'] / $v['maks'] * 100)])
            ->sortBy('pct')->values()->all();

        return [$weak, $attempts->isNotEmpty()];
    }

    /**
     * @param  array<int,array{name:string,pct:int}>  $weak
     * @return array<int,array{key:string,title:string,detail:string,url:?string,done:bool}>
     */
    private function tasksFor(StudyProfile $profile, int $offset, ?int $daysLeft, array $weak, bool $hasAttempts): array
    {
        $examUrl = $profile->examType ? route('exam.show', $profile->examType) : route('account.exams');
        $task = fn (string $key, string $title, string $detail, ?string $url = null) => compact('key', 'title', 'detail', 'url') + ['done' => false];

        if ($daysLeft === 0) {
            return [$task('exam', 'Hari ujian', 'Bawa berkas dan kartu peserta, datang lebih awal, sarapan ringan. Kamu sudah berlatih, tinggal tenang.')];
        }

        $tasks = [];

        if ($daysLeft === 1) {
            $tasks[] = $task('rest', 'Persiapan akhir', 'Siapkan berkas dan lokasi ujian, baca ulang catatan rumus atau materi inti, lalu tidur cukup. Tidak perlu simulasi baru.');
            $tasks[] = $task('review', 'Baca ulang pembahasan', 'Buka hasil simulasi terakhir dan baca lagi pembahasan soal yang sempat salah.', route('account.history'));

            return $tasks;
        }

        if (! $hasAttempts && $offset === 0) {
            $tasks[] = $task('baseline', 'Ukur kemampuan awal', 'Kerjakan satu simulasi mode coba supaya rencana bisa menyesuaikan bagian yang lemah.', $examUrl);
        }

        if ($hasAttempts) {
            $tasks[] = $task('retake', 'Ulangi simulasi terakhir', 'Kerjakan lagi soal dari simulasi terakhirmu, lalu cek apakah skornya naik. Buka dari Riwayat.', route('account.history'));
        } elseif ($offset > 0) {
            $tasks[] = $task('drill', 'Kerjakan satu simulasi', 'Satu simulasi mode coba cukup untuk menjaga ritme belajar.', $examUrl);
        }

        $isFullDay = $daysLeft !== null ? ($daysLeft === 2 || ($daysLeft > 2 && $daysLeft % 7 === 0)) : ($offset === 6);
        if ($isFullDay && $hasAttempts) {
            $tasks[] = $task('full', $daysLeft === 2 ? 'Simulasi penuh terakhir' : 'Simulasi penuh', 'Kerjakan dengan timer penuh dan tanpa membuka catatan, lalu bahas semua soal yang salah.', $examUrl);
        } elseif ($offset % 2 === 0 && $hasAttempts) {
            $focus = $weak[(int) ($offset / 2) % max(1, count($weak))] ?? null;
            $tasks[] = $task('drill', $focus ? 'Latihan fokus: '.$focus['name'] : 'Latihan simulasi', $focus ? 'Nilai rata-ratamu di bagian ini '.$focus['pct'].'%. Kerjakan simulasi mode coba dan perhatikan bagian ini.' : 'Kerjakan satu simulasi mode coba.', $examUrl);
        }

        return $tasks;
    }
}
