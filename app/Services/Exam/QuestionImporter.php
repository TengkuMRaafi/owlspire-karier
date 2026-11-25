<?php

namespace App\Services\Exam;

use App\Models\ExamSegment;
use App\Models\Question;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/** Mengimpor bank soal dari format lama (CI) maupun CSV. */
class QuestionImporter
{
    /** @var array<string,int|null> cache "tipe:segmen" => segment_id */
    private array $segmentCache = [];

    /** @return array{created:int, updated:int, skipped:int, errors:array<int,string>} */
    public function import(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach (array_values($rows) as $i => $row) {
            $line = $i + 1;
            if (! is_array($row)) {
                $result['skipped']++;
                $result['errors'][] = "Baris {$line}: format tidak dikenali.";
                continue;
            }

            $error = $this->importRow($row, $result);
            if ($error) {
                $result['skipped']++;
                if (count($result['errors']) < 25) {
                    $result['errors'][] = "Baris {$line}: {$error}";
                }
            }
        }

        return $result;
    }

    /** Menerima daftar baris, atau peta {tipe: {segmen: [..]}} seperti keluaran API lama. */
    public function parseJson(string $json): array
    {
        $data = json_decode($json, true);
        if (! is_array($data)) {
            throw new \InvalidArgumentException('Berkas bukan JSON yang valid.');
        }

        if (isset($data['questions']) && is_array($data['questions'])) {
            $data = $data['questions'];
        }

        // Ekspor bank soal CI: {exam_type, segments: {kode: {soal: [...]}}}.
        if (! array_is_list($data) && isset($data['segments']) && is_array($data['segments'])) {
            $type = (string) ($data['exam_type'] ?? $data['examType'] ?? '');
            $rows = [];
            foreach ($data['segments'] as $code => $segment) {
                $list = is_array($segment) ? ($segment['soal'] ?? $segment['questions'] ?? (array_is_list($segment) ? $segment : [])) : [];
                $segmentCode = is_array($segment) ? (string) ($segment['segment_id'] ?? $code) : (string) $code;
                foreach ((array) $list as $q) {
                    if (is_array($q)) {
                        $rows[] = $q + ['examType' => $type, 'segmentId' => $segmentCode];
                    }
                }
            }

            return $rows;
        }

        if (array_is_list($data)) {
            return $data;
        }

        $rows = [];
        foreach ($data as $type => $segments) {
            if (! is_array($segments)) {
                continue;
            }
            foreach ($segments as $segment => $questions) {
                foreach ((array) $questions as $q) {
                    if (is_array($q)) {
                        $rows[] = $q + ['examType' => $type, 'segmentId' => $segment];
                    }
                }
            }
        }

        return $rows;
    }

    public function parseCsv(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv); // buang BOM Excel
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);

        $header = null;
        $rows = [];
        while (($cols = fgetcsv($stream)) !== false) {
            if ($cols === [null] || $cols === []) {
                continue;
            }
            if ($header === null) {
                $header = array_map(fn ($h) => Str::snake(trim((string) $h)), $cols);
                continue;
            }

            $r = array_combine($header, array_pad(array_slice($cols, 0, count($header)), count($header), ''));
            $opts = [];
            foreach (['a', 'b', 'c', 'd', 'e'] as $letter) {
                $text = trim((string) ($r["option_{$letter}"] ?? ''));
                if ($text !== '') {
                    $opts[strtoupper($letter)] = $text;
                }
            }

            $rows[] = [
                'examType' => trim((string) ($r['exam_type'] ?? '')),
                'segmentId' => trim((string) ($r['segment'] ?? '')),
                'q' => trim((string) ($r['question'] ?? '')),
                'opts' => $opts,
                'ans' => trim((string) ($r['answer'] ?? '')),
                'exp' => trim((string) ($r['explanation'] ?? '')),
                'isTrial' => in_array(strtolower(trim((string) ($r['is_trial'] ?? ''))), ['1', 'true', 'ya', 'yes'], true),
            ];
        }
        fclose($stream);

        return $rows;
    }

    /** Mengubah ["A. teks", "B. teks"] atau {"A": "teks"} menjadi {"A": "teks", ...}. */
    public function normalizeOptions(mixed $opts): array
    {
        $clean = [];
        if (! is_array($opts)) {
            return $clean;
        }

        if (! array_is_list($opts)) {
            foreach ($opts as $key => $text) {
                $k = strtoupper(trim((string) $key));
                if (preg_match('/^[A-E]$/', $k) && trim((string) $text) !== '') {
                    $clean[$k] = trim((string) $text);
                }
            }
            ksort($clean);

            return $clean;
        }

        foreach ($opts as $i => $text) {
            $text = trim((string) $text);
            if ($text === '' || $i > 4) {
                continue;
            }
            $clean[chr(65 + $i)] = trim(preg_replace('/^[A-Ea-e][\.\)]\s*/u', '', $text));
        }

        return $clean;
    }

    private function importRow(array $row, array &$result): ?string
    {
        $body = trim((string) ($row['q'] ?? $row['body'] ?? $row['question'] ?? ''));
        if ($body === '') {
            return 'teks soal kosong.';
        }

        $segmentId = $this->segmentId((string) ($row['examType'] ?? $row['exam_type'] ?? ''), (string) ($row['segmentId'] ?? $row['segment_id'] ?? ''));
        if (! $segmentId) {
            return 'jenis ujian/segmen "'.($row['examType'] ?? $row['exam_type'] ?? '').'/'.($row['segmentId'] ?? $row['segment_id'] ?? '').'" belum dibuat di menu Jenis ujian.';
        }

        $options = $this->normalizeOptions($row['opts'] ?? $row['options'] ?? []);
        if (count($options) < 2) {
            return 'minimal 2 pilihan jawaban.';
        }

        $answer = strtoupper(trim((string) ($row['ans'] ?? $row['answer'] ?? '')));
        if (! isset($options[$answer])) {
            return "kunci jawaban \"{$answer}\" tidak ada di pilihan.";
        }

        $explanation = trim((string) ($row['exp'] ?? $row['explanation'] ?? ''));
        $source = trim((string) ($row['source'] ?? ''));
        // "source" dari bank lama hanya penanda asal ("database"); rujukan sungguhan ditambahkan ke pembahasan.
        if ($source !== '' && ! in_array(strtolower($source), ['database', 'questions.js', 'ci'], true) && ! str_contains($explanation, $source)) {
            $explanation = rtrim($explanation).($explanation !== '' ? ' ' : '').'Rujukan: '.$source.'.';
        }

        $difficulty = max(1, min(5, (int) ($row['difficulty'] ?? 2)));

        $attrs = [
            'segment_id' => $segmentId,
            'topic' => trim((string) ($row['topic'] ?? '')) ?: null,
            'body' => $body,
            'options' => $options,
            'answer' => $answer,
            'explanation' => $explanation !== '' ? $explanation : null,
            'difficulty' => $difficulty,
        ];
        $isTrial = (bool) ($row['isTrial'] ?? $row['is_trial'] ?? false);

        // Kolom image bersifat opsional; baru dipetakan setelah migrasi gambar diterapkan.
        if (Schema::hasColumn('questions', 'image')) {
            $attrs['image'] = trim((string) ($row['image'] ?? '')) ?: null;
        }

        $existing = null;
        if (! empty($row['uid'])) {
            $existing = Question::where('uid', $row['uid'])->first();
        }
        $existing ??= Question::where('segment_id', $segmentId)->where('body', $body)->first();
        // 'prev' = teks lama soal yang direvisi, supaya baris yang sudah ada diperbarui, bukan digandakan.
        if (! $existing && ! empty($row['prev'])) {
            $existing = Question::where('segment_id', $segmentId)->where('body', trim((string) $row['prev']))->first();
        }

        if ($existing) {
            // Penanda soal coba (gratis) yang sudah ada tidak diturunkan oleh impor ulang.
            $attrs['is_trial'] = $existing->is_trial || $isTrial;
            if ($attrs['explanation'] === null) {
                unset($attrs['explanation']);
            }
            $existing->update($attrs);
            $result['updated']++;
        } else {
            Question::create($attrs + ['is_trial' => $isTrial, 'uid' => ! empty($row['uid']) ? (string) $row['uid'] : (string) Str::uuid()]);
            $result['created']++;
        }

        return null;
    }

    private function segmentId(string $typeCode, string $segmentCode): ?int
    {
        $key = strtolower($typeCode).':'.strtolower($segmentCode);
        if (! array_key_exists($key, $this->segmentCache)) {
            // Beberapa berkas memakai "studi-kasus" untuk segmen yang kodenya "kasus".
            $code = strtolower(trim($segmentCode));
            $code = ['studi-kasus' => 'kasus', 'studi_kasus' => 'kasus'][$code] ?? $code;
            $this->segmentCache[$key] = ExamSegment::query()
                ->where('code', $code)
                ->whereHas('type', fn ($q) => $q->where('code', strtolower($typeCode)))
                ->value('id');
        }

        return $this->segmentCache[$key];
    }
}
