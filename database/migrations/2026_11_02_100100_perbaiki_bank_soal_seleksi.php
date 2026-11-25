<?php

use App\Models\ExamType;
use App\Services\Exam\QuestionImporter;
use Illuminate\Database\Migrations\Migration;

/** Memperbaiki soal seleksi yang salah/ambigu dan menyamakan ambang CPNS dengan nilai resmi (65/80/166). */
return new class extends Migration
{
    public function up(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/soal-awal.json')), true);

        // Pada database baru segmen belum ada; baris dilewati dan ExamSeeder memuat data yang sudah benar.
        app(QuestionImporter::class)->import($data['questions']);

        foreach ($data['types'] as $t) {
            $type = ExamType::where('code', $t['code'])->first();
            if ($type) {
                $type->passing_rules = $t['passing_rules'];
                $type->save();
            }
        }
    }

    public function down(): void
    {
        // Perbaikan konten tidak dibalik.
    }
};
