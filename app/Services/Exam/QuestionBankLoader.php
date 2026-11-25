<?php

namespace App\Services\Exam;

/** Memuat seluruh berkas JSON di database/seeders/data/bank-soal ke tabel questions (aman diulang: soal yang sama diperbarui, bukan digandakan). */
class QuestionBankLoader
{
    public function __construct(private QuestionImporter $importer)
    {
    }

    public function directory(): string
    {
        return database_path('seeders/data/bank-soal');
    }

    /** @return array{files:int, created:int, updated:int, skipped:int, errors:array<int,string>, per_file:array<string,array>} */
    public function loadAll(?string $dir = null): array
    {
        $dir ??= $this->directory();
        $total = ['files' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [], 'per_file' => []];

        $files = glob(rtrim($dir, '/\\').DIRECTORY_SEPARATOR.'*.json') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            try {
                $rows = $this->importer->parseJson((string) file_get_contents($file));
                $r = $this->importer->import($rows);
            } catch (\Throwable $e) {
                $total['errors'][] = "{$name}: {$e->getMessage()}";

                continue;
            }

            $total['files']++;
            foreach (['created', 'updated', 'skipped'] as $k) {
                $total[$k] += $r[$k];
            }
            $total['per_file'][$name] = ['created' => $r['created'], 'updated' => $r['updated'], 'skipped' => $r['skipped']];
            foreach (array_slice($r['errors'], 0, 3) as $err) {
                $total['errors'][] = "{$name}: {$err}";
            }
        }

        return $total;
    }
}
