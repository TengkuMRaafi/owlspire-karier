<?php

use App\Models\ExamSegment;
use App\Services\Exam\QuestionImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Menambah 346 soal SKD CPNS (TWK 115, TIU 116, TKP 115) dari bank-soal/cpns-tambahan.json.
 * Soal yang sama diperbarui (uid atau teks soal), bukan digandakan, jadi aman dijalankan ulang.
 * Pada database baru, DatabaseSeeder memuatnya lewat QuestionBankLoader (semua berkas di folder bank-soal).
 */
return new class extends Migration
{
    public function up(): void
    {
        $file = database_path('seeders/data/bank-soal/cpns-tambahan.json');
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
