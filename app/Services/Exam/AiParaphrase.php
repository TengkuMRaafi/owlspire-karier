<?php

namespace App\Services\Exam;

use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\Ai\GroqClient;
use Illuminate\Support\Facades\Cache;

/** Parafrase pembahasan dan ringkasan hasil. Hasil di-cache agar hemat kuota dan cepat dibuka ulang. */
class AiParaphrase
{
    private const SYSTEM = 'Kamu Owlspire, pendamping belajar untuk peserta tes seleksi di Indonesia. Tulis dalam Bahasa Indonesia yang santai tapi jelas, tanpa markdown, tanpa tanda bintang, tanpa emoji. Jangan menambah fakta, angka, atau kunci jawaban yang tidak ada di data yang diberikan.';

    private const CHAT_SYSTEM = 'Kamu Owlspire, asisten yang menjelaskan hasil simulasi ujian kepada pesertanya, seperti teman belajar yang sabar. Jawab dalam Bahasa Indonesia santai tapi jelas, maksimal 4 kalimat pendek (boleh dua paragraf pendek), tanpa markdown, tanpa tanda bintang, tanpa emoji. Pakai hanya angka dan fakta dari data hasil yang diberikan; jangan mengarang soal, kunci jawaban, atau angka. Kalau pertanyaan di luar hasil ujian atau cara belajar, tolak dengan sopan dan arahkan kembali ke hasilnya. Tutup dengan satu langkah konkret bila relevan.';

    public function __construct(private GroqClient $groq)
    {
    }

    public function enabled(): bool
    {
        return $this->groq->enabled();
    }

    /** Pembahasan soal dengan kata-kata lebih sederhana. Cache dipakai bersama semua peserta. */
    public function explain(Question $q): ?string
    {
        if (blank($q->explanation) || ! $this->enabled()) {
            return null;
        }

        $key = 'ai:explain:'.$q->id.':'.md5($q->body.$q->explanation.$q->answer);

        return $this->remember($key, function () use ($q) {
            $options = collect($q->options ?? [])->map(fn ($t, $k) => "$k. $t")->implode("\n");
            $user = "Soal:\n{$q->body}\n\nPilihan:\n{$options}\n\nKunci: {$q->answer}\n\nPembahasan resmi:\n{$q->explanation}\n\n"
                .'Tulis ulang pembahasan itu dengan kalimatmu sendiri dalam 3 sampai 4 kalimat: kenapa kunci benar, lalu satu jebakan yang sering membuat orang salah pilih. Jangan menyalin kalimat aslinya.';

            return [['role' => 'system', 'content' => self::SYSTEM], ['role' => 'user', 'content' => $user]];
        }, 1000);
    }

    /** Ringkasan hasil satu pengerjaan: kondisi, prioritas, rencana belajar singkat. */
    public function summary(ExamAttempt $attempt, array $insights, bool $brief = false): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->remember('ai:summary:'.$attempt->id.($brief ? ':b' : ''), function () use ($attempt, $insights, $brief) {
            $data = [
                'ujian' => $attempt->type->name,
                'lulus' => $attempt->passed,
                'skor' => $attempt->total_score,
                'statistik' => collect($insights['stats'])->only(['accuracy', 'precision', 'kosong', 'salah', 'benar', 'avg_seconds', 'allowed_seconds', 'stamina_delta'])->all(),
                'passing_grade' => $insights['passing']['items'] ?? [],
                'bagian' => collect($insights['segments'])->map(fn ($s) => collect($s)->only(['name', 'poin', 'maks', 'pct', 'cause', 'lost', 'recoverable'])->all())->all(),
            ];
            $ask = $brief
                ? 'Ini mode coba dengan soal lebih sedikit dari ujian asli. Tulis SATU paragraf pendek (maksimal 3 kalimat): posisimu terhadap ambang sebagai gambaran saja, lalu satu bagian yang paling perlu dikejar. Sapa dengan "kamu".'
                : 'Tulis ringkasan untuk peserta dalam tiga paragraf pendek (masing-masing 2 sampai 3 kalimat): (1) posisimu terhadap passing grade, (2) bagian yang paling perlu dikejar dan penyebabnya, (3) rencana belajar 7 hari yang konkret. Sapa dengan "kamu".';
            $user = 'Data hasil ujian (JSON):'."\n".json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n".$ask;

            return [['role' => 'system', 'content' => self::SYSTEM], ['role' => 'user', 'content' => $user]];
        }, $brief ? 700 : 1400);
    }

    /** Jawab pertanyaan bebas peserta tentang hasilnya. Tidak di-cache karena tiap pertanyaan berbeda. */
    public function ask(ExamAttempt $attempt, array $insights, string $question, array $history = []): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $data = [
            'ujian' => $attempt->type->name,
            'mode' => $attempt->mode === 'full' ? 'ujian penuh' : 'mode coba (soal lebih sedikit dari aslinya)',
            'lulus' => $attempt->passed,
            'skor' => $attempt->total_score,
            'statistik' => collect($insights['stats'])->only(['accuracy', 'precision', 'kosong', 'salah', 'benar', 'total', 'avg_seconds', 'allowed_seconds'])->all(),
            'passing_grade' => $insights['passing']['items'] ?? [],
            'bagian' => collect($insights['segments'])->map(fn ($s) => collect($s)->only(['name', 'poin', 'maks', 'pct', 'threshold', 'gap', 'benar', 'salah', 'kosong', 'acc_answered', 'answered_rate', 'avg_seconds', 'cause', 'recoverable', 'level', 'measures', 'diagnosis', 'train'])->all())->all(),
        ];

        $messages = [['role' => 'system', 'content' => self::CHAT_SYSTEM."\n\nData hasil (JSON):\n".json_encode($data, JSON_UNESCAPED_UNICODE)]];
        foreach (array_slice($history, -6) as $h) {
            $messages[] = ['role' => $h['role'] === 'assistant' ? 'assistant' : 'user', 'content' => (string) $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        $text = $this->groq->chat($messages, 600);

        return $text !== null ? trim(preg_replace('/[*_#`]+/', '', $text)) : null;
    }

    /** Jawaban berbasis aturan untuk pertanyaan peserta, dipakai bila Groq belum aktif atau gagal. */
    public function askFallback(array $insights, string $question): string
    {
        $q = mb_strtolower($question);
        $parts = explode("\n\n", $this->fallback($insights, false));

        foreach ($insights['segments'] as $s) {
            $abbr = preg_match('/\(([^)]+)\)/', $s['name'], $m) ? mb_strtolower($m[1]) : null;
            if (str_contains($q, mb_strtolower($s['name'])) || ($abbr !== null && preg_match('/\b'.preg_quote($abbr, '/').'\b/u', $q))) {
                return $this->segmentAnswer($s);
            }
        }

        if (preg_match('/rencana|7 hari|jadwal/u', $q)) {
            return $parts[2] ?? $parts[0];
        }
        if (preg_match('/waktu|lambat|cepat|tempo|durasi/u', $q)) {
            $st = $insights['stats'];
            $fmt = fn ($x) => $x === null ? '-' : (intdiv((int) $x, 60) > 0 ? intdiv((int) $x, 60).' mnt '.((int) $x % 60).' dtk' : (int) $x.' dtk');

            return 'Rata-rata kamu memakai '.$fmt($st['avg_seconds'] ?? null).' per soal, sedangkan jatahnya sekitar '.$fmt($st['allowed_seconds'] ?? null).'. '
                .(($st['avg_seconds'] ?? null) !== null && ($st['allowed_seconds'] ?? null) !== null && $st['avg_seconds'] < $st['allowed_seconds'] * 0.4
                    ? 'Kamu menjawab jauh lebih cepat dari jatah, jadi pakai waktu lebih untuk membaca soal dan memeriksa ulang.'
                    : 'Atur tempo per soal supaya tidak ada bagian yang kehabisan waktu.');
        }
        if (preg_match('/dulu|fokus|prioritas|kejar|lemah|mana/u', $q)) {
            return $parts[1] ?? $parts[0];
        }
        if (preg_match('/lulus|passing|ambang|skor|nilai|segini/u', $q)) {
            return $parts[0];
        }

        return $parts[0].' '.($parts[1] ?? '');
    }

    private function segmentAnswer(array $s): string
    {
        $thr = $s['threshold'] ?? null;
        $pos = $thr !== null
            ? ($s['gap'] >= 0 ? 'sudah di atas ambang' : 'masih di bawah ambang '.rtrim(rtrim(number_format($thr, 1), '0'), '.').'%')
            : 'tingkatnya '.($s['level'] ?? 'cukup');
        $first = fn ($t) => trim((string) (preg_split('/(?<=[.!?])\s+/', trim((string) $t))[0] ?? ''));

        return $s['name'].': kamu dapat '.$s['poin'].' dari '.$s['maks'].' poin ('.$s['pct'].'%), '.$pos.'. '
            .$first($s['diagnosis'] ?? '').' '.$first($s['train'] ?? '');
    }

    /** Ringkasan berbasis aturan, dipakai bila Groq belum diaktifkan atau gagal, supaya blok ringkasan tidak pernah kosong. */
    public function fallback(array $insights, bool $brief = false): string
    {
        $pg = $insights['passing'];
        $prio = $insights['priority'];
        $top = $prio[0] ?? null;
        $second = $prio[1] ?? null;

        if ($pg['items'] === []) {
            $p1 = 'Hasil ini menunjukkan seberapa jauh kamu sudah siap. Pakai angka di kartu atas sebagai pegangan awal.';
        } elseif ($pg['all_pass']) {
            $p1 = 'Skormu menembus semua ambang passing grade, jadi posisimu aman. Tantangan berikutnya menjaga hasil ini tetap stabil di percobaan lain.';
        } else {
            $kurang = collect($pg['items'])->where('pass', false)->map(fn ($i) => $i['name'].' kurang '.abs($i['gap']).' poin')->implode(', ');
            $p1 = 'Kamu belum menembus passing grade: '.$kurang.'. Selisih ini masih bisa dikejar dengan latihan terarah.';
        }

        $p2 = $top
            ? 'Fokus utama ada di '.$top['name'].': '.$top['lost'].' soal salah atau kosong dan sekitar '.$top['recoverable'].' poin masih bisa diambil. '
                .match ($top['cause']) {
                    'waktu' => 'Penyebab terbesarnya waktu, jadi latih tempo sebelum menambah materi.',
                    'materi' => 'Penyebab terbesarnya materi, jadi ulang konsep dasarnya dulu.',
                    default => 'Rapikan soal yang salah dengan membaca pembahasannya satu per satu.',
                }
            : 'Belum ada bagian yang menonjol sebagai titik lemah. Pertahankan ritme belajarmu.';

        $p3 = 'Rencana 7 hari: hari 1 sampai 3 latih '.($top['name'] ?? 'bagian terlemahmu').', hari 4 dan 5 '
            .($second ? 'latih '.$second['name'] : 'ulang soal yang pernah salah').', hari 6 kerjakan satu set dengan timer, hari 7 ulangi ujian penuh dan bandingkan skornya.';

        return $brief ? $p1.' '.$p2 : $p1."\n\n".$p2."\n\n".$p3;
    }

    private function remember(string $key, \Closure $messages, int $tokens): ?string
    {
        if (($hit = Cache::get($key)) !== null) {
            return $hit;
        }

        $text = $this->groq->chat($messages(), $tokens);
        if ($text !== null) {
            $text = trim(preg_replace('/[*_#`]+/', '', $text));
            Cache::put($key, $text, now()->addDays(30)); // gagal tidak di-cache supaya bisa dicoba lagi
        }

        return $text;
    }
}
