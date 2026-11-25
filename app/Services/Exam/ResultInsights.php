<?php

namespace App\Services\Exam;

use App\Models\ExamAttempt;

/**
 * Statistik evaluasi diri untuk halaman hasil ujian: bukan sekadar benar/salah,
 * tetapi pola jawaban (presisi, kecepatan, stamina, keraguan, posisi di antara peserta)
 * dan diagnosis per segmen (TWK, TIU, TKP, dan seterusnya) lengkap dengan prioritas belajar.
 */
class ResultInsights
{
    /** Penjelasan tiap segmen: apa yang diukur dan kenapa nilai bisa turun. */
    private const SEGMENT_INFO = [
        'twk' => [
            'measures' => 'Pemahaman Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika, sejarah perjuangan, dan nasionalisme.',
            'nature' => 'Soal konsep dan hafalan terstruktur. Kalau nilainya rendah, penyebab terbesarnya hampir selalu materi yang belum dikuasai, bukan kecepatan.',
            'train' => 'Buat ringkasan per sila, pasal penting UUD 1945, dan lini masa sejarah, lalu ulangi soal yang salah dengan jeda beberapa hari.',
        ],
        'tiu' => [
            'measures' => 'Kemampuan verbal (sinonim, analogi, silogisme), numerik (hitungan, deret, perbandingan), dan figural (pola gambar).',
            'nature' => 'Soal ini menghukum lambat dan ceroboh. Skor turun karena waktu habis di soal hitungan panjang, atau karena pola belum dikenali.',
            'train' => 'Latih rumus cepat dan pola deret, kerjakan dengan timer per soal, dan lewati soal yang menghabiskan lebih dari satu menit.',
        ],
        'tkp' => [
            'measures' => 'Sikap kerja, pelayanan publik, integritas, kerja sama, dan jejaring kerja lewat situasi nyata.',
            'nature' => 'Tidak ada jawaban hafalan: yang dinilai adalah respons paling tepat menurut nilai ASN. Ambang kelulusannya biasanya paling tinggi, jadi satu segmen ini sering menentukan lulus atau tidak.',
            'train' => 'Biasakan memilih respons yang melayani publik, patuh aturan, dan menjaga hubungan kerja, lalu baca pembahasan kenapa pilihan lain kurang tepat.',
        ],
        'tkd' => [
            'measures' => 'Kemampuan dasar: logika, numerik, verbal, dan pemahaman bacaan untuk seleksi BUMN.',
            'nature' => 'Gabungan penalaran dan kecepatan. Kesalahan biasanya datang dari soal hitungan dan bacaan panjang.',
            'train' => 'Latih soal hitungan bertahap dan membaca cepat dengan mencari inti paragraf lebih dulu.',
        ],
        'akhlak' => [
            'measures' => 'Pemahaman nilai Amanah, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif dalam situasi kerja.',
            'nature' => 'Soal berbasis situasi: jawaban terbaik adalah yang paling selaras dengan nilai, bukan yang paling aman bagi diri sendiri.',
            'train' => 'Pahami perilaku utama tiap nilai, lalu cocokkan dengan contoh kasus di pembahasan.',
        ],
        'english' => [
            'measures' => 'Tata bahasa, kosakata, dan pemahaman bacaan bahasa Inggris tingkat kerja.',
            'nature' => 'Skor naik cepat bila kosakata dan pola kalimat dilatih rutin.',
            'train' => 'Baca teks pendek tiap hari dan catat pola kalimat yang sering salah.',
        ],
        'psikotes' => [
            'measures' => 'Kecenderungan kepribadian, ketahanan, dan konsistensi sikap.',
            'nature' => 'Yang dinilai konsistensi jawaban dan kematangan sikap, bukan hafalan.',
            'train' => 'Jawab jujur dan konsisten, lalu pelajari pola pertanyaan yang diulang dengan kalimat berbeda.',
        ],
        'kecerdasan' => [
            'measures' => 'Penalaran umum: verbal, numerik, dan logika.',
            'nature' => 'Butuh ketelitian dan pola berpikir yang runtut, bukan hanya kecepatan.',
            'train' => 'Kerjakan soal penalaran bertingkat dan tulis langkahnya sebelum memilih jawaban.',
        ],
        'kecermatan' => [
            'measures' => 'Ketelitian, kecepatan, dan daya tahan konsentrasi dalam mencocokkan simbol dan angka.',
            'nature' => 'Murni soal kecermatan: skor turun karena kehilangan fokus, bukan karena materi.',
            'train' => 'Latihan singkat tapi rutin dengan target waktu, dan jaga ritme alih-alih memaksa cepat.',
        ],
    ];

    private const GENERIC_INFO = [
        'measures' => 'Kemampuan khusus pada bagian ini sesuai kebutuhan bidangnya.',
        'nature' => 'Bandingkan angka akurasi dan tingkat terjawab di bawah untuk melihat apakah kendalanya materi atau waktu.',
        'train' => 'Ulangi soal yang salah dari bagian ini, lalu coba lagi dengan batas waktu.',
    ];

    /** @return array<string,mixed> */
    public function build(ExamAttempt $attempt): array
    {
        $attempt->loadMissing('type', 'answers.question.segment');

        $score = $attempt->score ?? [];
        $answers = $attempt->answers;
        $totalQ = max(1, (int) array_sum(array_column($score, 'total')));
        $benar = (int) array_sum(array_column($score, 'benar'));
        $salah = (int) array_sum(array_column($score, 'salah'));
        $kosong = (int) array_sum(array_column($score, 'kosong'));
        $dijawab = $benar + $salah;
        $maks = (int) array_sum(array_column($score, 'maks'));
        $poin = (int) array_sum(array_column($score, 'poin'));
        $secs = (int) ($attempt->durationSeconds() ?? 0);

        $timeBySeg = $this->timePerSegment($attempt);
        $peers = $this->peers($attempt);
        $rules = $attempt->type->passing_rules ?? [];

        // ---- Per segmen ----
        $segments = [];
        foreach ($score as $code => $s) {
            $total = max(1, (int) $s['total']);
            $answered = (int) $s['benar'] + (int) $s['salah'];
            $pct = $s['maks'] > 0 ? (int) round($s['poin'] / $s['maks'] * 100) : 0;
            $threshold = isset($rules['segments'][$code]) ? (float) $rules['segments'][$code] : null;
            $perCorrect = $s['benar'] > 0 ? $s['poin'] / $s['benar'] : ($s['maks'] / $total);
            $info = self::SEGMENT_INFO[$code] ?? self::GENERIC_INFO;

            $accAnswered = $answered > 0 ? (int) round($s['benar'] / $answered * 100) : 0;
            $answeredRate = (int) round($answered / $total * 100);
            $emptyShare = $s['kosong'] / $total;

            if ($emptyShare >= 0.25) {
                $cause = 'waktu';
                $diagnosis = 'Sebanyak '.$s['kosong'].' dari '.$s['total'].' soal dibiarkan kosong. Kendala utamamu di sini adalah waktu atau keberanian menjawab, bukan semata materi. Soal yang kamu jawab justru '.($accAnswered >= 60 ? 'cukup akurat ('.$accAnswered.'%)' : 'baru '.$accAnswered.'% benar').'.';
            } elseif ($answered > 0 && $accAnswered < 60) {
                $cause = 'materi';
                $diagnosis = 'Hampir semua soal kamu jawab ('.$answeredRate.'%), tetapi baru '.$accAnswered.'% yang benar. Kendalanya ada di pemahaman materi atau jebakan pilihan jawaban, jadi menambah kecepatan tidak akan banyak membantu.';
            } elseif ($pct >= 75) {
                $cause = 'kuat';
                $diagnosis = 'Bagian ini sudah kuat: '.$answeredRate.'% soal terjawab dengan akurasi '.$accAnswered.'%. Jaga dengan latihan soal yang lebih rumit.';
            } else {
                $cause = 'seimbang';
                $diagnosis = 'Akurasi '.$accAnswered.'% pada soal yang dijawab sudah wajar. Sisa selisihnya datang dari beberapa soal yang salah atau kosong, jadi membahas soal-soal itu satu per satu akan paling efektif.';
            }

            if ($threshold !== null) {
                $gap = round($pct - $threshold, 1);
                $level = $gap < 0 ? 'weak' : ($gap < 10 ? 'ok' : 'strong');
            } else {
                $gap = null;
                $level = $pct >= 75 ? 'strong' : ($pct >= 50 ? 'ok' : 'weak');
            }

            $lost = (int) $s['salah'] + (int) $s['kosong'];
            $segments[] = [
                'code' => $code,
                'name' => $s['nama'],
                'benar' => (int) $s['benar'], 'salah' => (int) $s['salah'], 'kosong' => (int) $s['kosong'], 'total' => (int) $s['total'],
                'poin' => (int) $s['poin'], 'maks' => (int) $s['maks'],
                'pct' => $pct,
                'acc_answered' => $accAnswered,
                'answered_rate' => $answeredRate,
                'threshold' => $threshold,
                'gap' => $gap,
                'level' => $level,
                'cause' => $cause,
                'diagnosis' => $diagnosis,
                'measures' => $info['measures'],
                'nature' => $info['nature'],
                'train' => $info['train'],
                'avg_seconds' => isset($timeBySeg[$code]) && $timeBySeg[$code]['n'] > 0 ? (int) round($timeBySeg[$code]['sum'] / $timeBySeg[$code]['n']) : null,
                'peer_pct' => $peers['segment_avg'][$code] ?? null,
                'recoverable' => (int) round($lost * $perCorrect),
                'lost' => $lost,
            ];
        }

        // ---- Statistik umum ----
        $allowedPerQ = $attempt->type->duration_seconds ? $attempt->type->duration_seconds / $totalQ : null;
        $avgPerQ = $totalQ > 0 && $secs > 0 ? $secs / $totalQ : null;

        $half = intdiv(max(2, $answers->count()), 2);
        $first = $answers->take($half);
        $second = $answers->slice($half);
        $accOf = fn ($c) => $c->count() ? (int) round($c->filter(fn ($a) => $a->is_correct === true)->count() / $c->count() * 100) : null;
        $accFirst = $accOf($first);
        $accSecond = $accOf($second);

        $flagged = $answers->filter(fn ($a) => $a->flagged);
        $flaggedAnswered = $flagged->filter(fn ($a) => $a->selected !== null && $a->selected !== '');
        $flaggedRight = $flaggedAnswered->filter(fn ($a) => $a->is_correct === true)->count();
        $unflagged = $answers->filter(fn ($a) => ! $a->flagged && $a->selected !== null && $a->selected !== '');
        $unflaggedAcc = $unflagged->count() ? (int) round($unflagged->filter(fn ($a) => $a->is_correct === true)->count() / $unflagged->count() * 100) : null;

        $pcts = array_column($segments, 'pct');
        $spread = $pcts ? max($pcts) - min($pcts) : 0;

        $stats = [
            'benar' => $benar, 'salah' => $salah, 'kosong' => $kosong, 'total' => $totalQ,
            'poin' => $poin, 'maks' => $maks, 'pct' => $maks > 0 ? (int) round($poin / $maks * 100) : 0,
            'accuracy' => (int) round($benar / $totalQ * 100),
            'precision' => $dijawab > 0 ? (int) round($benar / $dijawab * 100) : 0,
            'answered_rate' => (int) round($dijawab / $totalQ * 100),
            'avg_seconds' => $avgPerQ !== null ? (int) round($avgPerQ) : null,
            'allowed_seconds' => $allowedPerQ !== null ? (int) round($allowedPerQ) : null,
            'time_used_pct' => $attempt->type->duration_seconds ? (int) min(100, round($secs / $attempt->type->duration_seconds * 100)) : null,
            'acc_first' => $accFirst, 'acc_second' => $accSecond,
            'stamina_delta' => $accFirst !== null && $accSecond !== null ? $accSecond - $accFirst : null,
            'flagged' => $flagged->count(),
            'flagged_right' => $flaggedRight,
            'flagged_answered' => $flaggedAnswered->count(),
            'unflagged_acc' => $unflaggedAcc,
            'spread' => $spread,
            'wrong_vs_empty' => ($salah + $kosong) > 0 ? (int) round($salah / ($salah + $kosong) * 100) : null,
        ];

        $segmentsByPriority = collect($segments)->sortBy([
            fn ($a, $b) => ($a['gap'] ?? 100) <=> ($b['gap'] ?? 100),
            fn ($a, $b) => $a['pct'] <=> $b['pct'],
        ])->values()->all();

        return [
            'stats' => $stats,
            'segments' => $segments,
            'priority' => $segmentsByPriority,
            'peers' => $peers + ['percentile' => $peers['percentile'] ?? null],
            'passing' => $this->passing($attempt, $segments, $rules),
            'rank' => $this->rank($attempt),
            'notes' => $this->notes($stats, $segments, $segmentsByPriority, $peers),
        ];
    }

    /**
     * Passing grade nyata: CPNS per segmen dalam poin (TWK 65, TIU 80, TKP 166 dari Kepmenpan RB 321/2024),
     * jenis lain memakai ambang persen total. Ambang poin = persen x poin maksimum segmen.
     */
    private function passing(ExamAttempt $attempt, array $segments, array $rules): array
    {
        $items = [];
        if (! empty($rules['segments'])) {
            foreach ($segments as $s) {
                $pctNeed = $rules['segments'][$s['code']] ?? null;
                if ($pctNeed === null) {
                    continue;
                }
                $need = (int) round($pctNeed / 100 * $s['maks']);
                $items[] = ['code' => $s['code'], 'name' => $s['name'], 'poin' => $s['poin'], 'maks' => $s['maks'], 'need' => $need, 'pass' => $s['poin'] >= $need, 'gap' => $s['poin'] - $need];
            }
            $mode = 'segment';
            $source = $attempt->type->code === 'cpns' ? 'Kepmenpan RB No. 321 Tahun 2024 (SKD CPNS)' : 'Ambang per segmen';
        } else {
            $min = (float) ($rules['min_percent'] ?? 60);
            $maks = (int) array_sum(array_column($segments, 'maks'));
            $poin = (int) array_sum(array_column($segments, 'poin'));
            $need = (int) ceil($min / 100 * $maks);
            $items[] = ['code' => 'total', 'name' => 'Skor total', 'poin' => $poin, 'maks' => $maks, 'need' => $need, 'pass' => $poin >= $need, 'gap' => $poin - $need];
            $mode = 'overall';
            $source = 'Ambang simulasi '.rtrim(rtrim(number_format($min, 1), '0'), '.').'% dari skor total (acuan internal, bukan angka resmi instansi)';
        }

        return [
            'mode' => $mode,
            'source' => $source,
            'items' => $items,
            'all_pass' => $items !== [] && collect($items)->every(fn ($i) => $i['pass']),
            'failed' => collect($items)->where('pass', false)->pluck('name')->values()->all(),
        ];
    }

    /** Peringkat di antara pengerjaan selesai pada jenis ujian dan mode yang sama (mode coba dibandingkan dengan mode coba). */
    private function rank(ExamAttempt $attempt): ?array
    {
        $base = ExamAttempt::query()->where('exam_type_id', $attempt->exam_type_id)->where('mode', $attempt->mode)->whereNotNull('finished_at');
        $of = (clone $base)->count();
        if ($of === 0) {
            return null;
        }

        return ['pos' => (clone $base)->where('total_score', '>', (int) $attempt->total_score)->count() + 1, 'of' => $of];
    }

    /** Catatan evaluasi diri berbentuk kalimat, diurutkan dari yang paling penting. */
    private function notes(array $s, array $segments, array $priority, array $peers): array
    {
        $notes = [];
        $weak = $priority[0] ?? null;
        $strong = collect($segments)->sortByDesc('pct')->first();

        if ($weak && $strong && $weak['code'] !== $strong['code']) {
            $notes[] = ['tone' => 'weak', 'title' => 'Prioritas pertama', 'text' => $weak['name'].' ('.$weak['pct'].'%) adalah titik terlemahmu. Memperbaiki '.$weak['lost'].' soal di sini bernilai sampai '.$weak['recoverable'].' poin, paling besar dibanding bagian lain. Sebaliknya '.$strong['name'].' ('.$strong['pct'].'%) sudah bisa kamu pertahankan.'];
        }

        if ($s['kosong'] > 0 && $s['salah'] > 0) {
            if ($s['kosong'] > $s['salah']) {
                $notes[] = ['tone' => 'tip', 'title' => 'Kendala utamamu: waktu', 'text' => 'Soal kosong ('.$s['kosong'].') lebih banyak daripada soal salah ('.$s['salah'].'). Akurasimu pada soal yang dijawab '.$s['precision'].'%, jadi latihan dengan batas waktu akan menaikkan skor lebih cepat daripada menambah materi baru.'];
            } else {
                $notes[] = ['tone' => 'tip', 'title' => 'Kendala utamamu: ketepatan', 'text' => 'Soal salah ('.$s['salah'].') lebih banyak daripada soal kosong ('.$s['kosong'].'). Kamu berani menjawab, tetapi pilihannya belum tepat. Bacalah pembahasan soal yang salah untuk melihat pola jebakannya.'];
            }
        } elseif ($s['kosong'] === 0 && $s['salah'] > 0) {
            $notes[] = ['tone' => 'tip', 'title' => 'Semua soal terjawab', 'text' => 'Tidak ada soal kosong, jadi waktumu cukup. Selisih nilainya murni soal yang salah ('.$s['salah'].'), sehingga fokus pada pemahaman, bukan kecepatan.'];
        } elseif ($s['salah'] === 0 && $s['kosong'] > 0) {
            $notes[] = ['tone' => 'tip', 'title' => 'Semua jawabanmu benar', 'text' => 'Setiap soal yang kamu jawab benar, tetapi '.$s['kosong'].' soal dibiarkan kosong. Tidak ada pengurangan nilai untuk jawaban salah, jadi lebih baik berani menebak dengan pertimbangan.'];
        }

        if ($s['stamina_delta'] !== null && abs($s['stamina_delta']) >= 10) {
            $notes[] = $s['stamina_delta'] < 0
                ? ['tone' => 'weak', 'title' => 'Stamina menurun', 'text' => 'Akurasi paruh awal '.$s['acc_first'].'% turun menjadi '.$s['acc_second'].'% di paruh akhir. Pola ini biasanya tanda fokus menurun atau terburu-buru di akhir. Latih durasi penuh agar daya tahanmu naik.']
                : ['tone' => 'good', 'title' => 'Makin lama makin tajam', 'text' => 'Akurasi paruh awal '.$s['acc_first'].'% naik menjadi '.$s['acc_second'].'% di paruh akhir. Kamu butuh pemanasan, jadi kerjakan beberapa soal mudah lebih dulu agar ritmenya terbentuk.'];
        }

        if ($s['flagged_answered'] >= 2) {
            $rate = (int) round($s['flagged_right'] / $s['flagged_answered'] * 100);
            if ($s['unflagged_acc'] !== null && $rate > $s['unflagged_acc'] + 10) {
                $notes[] = ['tone' => 'good', 'title' => 'Intuisimu lebih baik dari perkiraanmu', 'text' => 'Soal yang kamu ragukan justru benar '.$rate.'%, lebih tinggi daripada soal yang kamu yakini ('.$s['unflagged_acc'].'%). Jangan terlalu cepat mengubah jawaban pertama.'];
            } elseif ($s['unflagged_acc'] !== null && $rate < $s['unflagged_acc'] - 10) {
                $notes[] = ['tone' => 'good', 'title' => 'Keraguanmu bisa dipercaya', 'text' => 'Soal yang kamu tandai ragu benar '.$rate.'%, dibanding '.$s['unflagged_acc'].'% pada soal yang yakin. Artinya kamu tahu mana yang lemah, jadi kumpulkan soal bertanda ragu sebagai bahan belajar.'];
            }
        }

        if ($s['spread'] >= 30) {
            $notes[] = ['tone' => 'tip', 'title' => 'Kemampuanmu belum merata', 'text' => 'Selisih bagian tertinggi dan terendahmu '.$s['spread'].' poin persentase. Pada seleksi dengan ambang per segmen, satu bagian yang jatuh bisa menggagalkan keseluruhan skor.'];
        }

        if (($peers['percentile'] ?? null) !== null) {
            $notes[] = ['tone' => $peers['percentile'] >= 50 ? 'good' : 'tip', 'title' => 'Posisimu di antara peserta', 'text' => 'Skormu lebih tinggi dari '.$peers['percentile'].'% peserta lain pada ujian ini (rata-rata peserta '.$peers['avg_pct'].'%, dari '.$peers['count'].' pengerjaan).'];
        }

        return $notes;
    }

    /** Rata-rata detik per soal terjawab, per segmen, dari selisih waktu jawaban berurutan. */
    private function timePerSegment(ExamAttempt $attempt): array
    {
        $rows = $attempt->answers->filter(fn ($a) => $a->answered_at !== null)->sortBy('answered_at')->values();
        $prev = $attempt->started_at;
        $out = [];
        foreach ($rows as $a) {
            $delta = $prev ? max(0, (int) $prev->diffInSeconds($a->answered_at, false)) : 0;
            $prev = $a->answered_at;
            if ($delta > 600) {
                continue; // jeda sangat panjang bukan waktu mengerjakan satu soal
            }
            $code = $a->question->segment->code ?? 'umum';
            $out[$code] ??= ['sum' => 0, 'n' => 0];
            $out[$code]['sum'] += $delta;
            $out[$code]['n']++;
        }

        return $out;
    }

    /** Pembanding: peserta lain pada jenis dan mode ujian yang sama (minimal 5 pengerjaan). */
    private function peers(ExamAttempt $attempt): array
    {
        $others = ExamAttempt::query()
            ->where('exam_type_id', $attempt->exam_type_id)
            ->where('mode', $attempt->mode)
            ->whereNotNull('finished_at')
            ->where('id', '!=', $attempt->id)
            ->latest('finished_at')->limit(300)
            ->get(['id', 'score', 'total_score']);

        $empty = ['count' => $others->count(), 'percentile' => null, 'avg_pct' => null, 'segment_avg' => []];
        if ($others->count() < 5) {
            return $empty;
        }

        $mine = (int) $attempt->total_score;
        $lower = $others->filter(fn ($o) => (int) $o->total_score < $mine)->count();
        $maxMine = array_sum(array_column($attempt->score ?? [], 'maks'));

        $avgPcts = $others->map(function ($o) {
            $s = $o->score ?? [];
            $m = array_sum(array_column($s, 'maks'));

            return $m > 0 ? array_sum(array_column($s, 'poin')) / $m * 100 : null;
        })->filter(fn ($v) => $v !== null);

        $segAvg = [];
        foreach (array_keys($attempt->score ?? []) as $code) {
            $vals = $others->map(function ($o) use ($code) {
                $s = $o->score[$code] ?? null;

                return $s && ($s['maks'] ?? 0) > 0 ? $s['poin'] / $s['maks'] * 100 : null;
            })->filter(fn ($v) => $v !== null);
            if ($vals->count() >= 5) {
                $segAvg[$code] = (int) round($vals->avg());
            }
        }

        return [
            'count' => $others->count(),
            'percentile' => $maxMine > 0 ? (int) round($lower / $others->count() * 100) : null,
            'avg_pct' => (int) round($avgPcts->avg() ?? 0),
            'segment_avg' => $segAvg,
        ];
    }
}
