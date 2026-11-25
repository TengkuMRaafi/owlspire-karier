<?php

namespace App\Console\Commands;

use App\Services\Exam\QuestionBankLoader;
use App\Services\Exam\QuestionImporter;
use Illuminate\Console\Command;

class ImportQuestionsCommand extends Command
{
    protected $signature = 'exam:import {file? : Berkas .json/.csv, atau folder berisi berkas .json} {--bank : Muat seluruh bank soal bawaan (database/seeders/data/bank-soal)}';

    protected $description = 'Mengimpor bank soal dari berkas JSON (format CI, daftar soal, atau per karier), folder, atau CSV.';

    public function handle(QuestionImporter $importer, QuestionBankLoader $loader): int
    {
        if ($this->option('bank')) {
            return $this->report($loader->loadAll());
        }

        $path = $this->argument('file');
        if (! $path) {
            $this->error('Isi jalur berkas/folder, atau pakai --bank untuk memuat bank soal bawaan.');

            return self::FAILURE;
        }
        if (is_dir($path)) {
            return $this->report($loader->loadAll($path));
        }
        if (! is_file($path)) {
            $this->error("Berkas tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $content = file_get_contents($path);
        try {
            $rows = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'csv'
                ? $importer->parseCsv($content)
                : $importer->parseJson($content);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $r = $importer->import($rows);
        $this->info("Selesai: {$r['created']} baru, {$r['updated']} diperbarui, {$r['skipped']} dilewati.");
        foreach ($r['errors'] as $err) {
            $this->warn($err);
        }

        return self::SUCCESS;
    }

    private function report(array $r): int
    {
        foreach ($r['per_file'] as $name => $x) {
            $this->line(sprintf('%-48s %4d baru, %4d diperbarui, %3d dilewati', $name, $x['created'], $x['updated'], $x['skipped']));
        }
        $this->info("Selesai: {$r['files']} berkas, {$r['created']} baru, {$r['updated']} diperbarui, {$r['skipped']} dilewati.");
        foreach ($r['errors'] as $err) {
            $this->warn($err);
        }

        return $r['files'] > 0 ? self::SUCCESS : self::FAILURE;
    }
}
