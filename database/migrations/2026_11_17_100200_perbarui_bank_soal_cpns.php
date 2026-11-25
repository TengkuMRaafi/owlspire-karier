<?php

use App\Models\ExamSegment;
use App\Services\Exam\QuestionImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Memuat ulang bank soal CPNS yang sudah direvisi: kunci yang keliru diperbaiki, posisi jawaban diseimbangkan,
 * soal diberi label topik, dan stok TIU ditambah. Soal yang sama diperbarui (uid atau teks soal), bukan digandakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $file = database_path('seeders/data/bank-soal/cpns.json');
        if (app()->runningUnitTests() || ! is_file($file) || ! ExamSegment::query()->exists()) {
            return;
        }

        $importer = app(QuestionImporter::class);
        $importer->import($importer->parseJson((string) file_get_contents($file)));
    }

    public function down(): void
    {
        // Isi bank soal tidak dibalik.
    }
};
