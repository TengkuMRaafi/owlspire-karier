<?php

use App\Models\ExamSegment;
use App\Services\Exam\QuestionImporter;
use App\Services\Learning\MateriSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deskripsi detail tiap jalur belajar (courses.description), isi tiap topik (lessons.details),
 * dan soal simulasi karier yang diambil dari materi jalur belajar (bank-soal/materi-belajar.json).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'description')) {
            Schema::table('courses', fn (Blueprint $t) => $t->longText('description')->nullable());
        }
        if (! Schema::hasColumn('lessons', 'details')) {
            Schema::table('lessons', fn (Blueprint $t) => $t->json('details')->nullable());
        }

        MateriSeeder::apply();

        // Pada database baru, jenis ujian belum ada saat migrasi berjalan; DatabaseSeeder memuat bank soal setelah CareerSeeder.
        $file = database_path('seeders/data/bank-soal/materi-belajar.json');
        if (! app()->runningUnitTests() && is_file($file) && ExamSegment::query()->exists()) {
            $importer = app(QuestionImporter::class);
            $importer->import($importer->parseJson((string) file_get_contents($file)));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lessons', 'details')) {
            Schema::table('lessons', fn (Blueprint $t) => $t->dropColumn('details'));
        }
        if (Schema::hasColumn('courses', 'description')) {
            Schema::table('courses', fn (Blueprint $t) => $t->dropColumn('description'));
        }
    }
};
