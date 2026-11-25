<?php

use App\Models\ExamSegment;
use App\Services\Exam\QuestionBankLoader;
use Illuminate\Database\Migrations\Migration;

/**
 * Memuat bank soal lengkap (CPNS, BUMN, Polri/TNI, dan 15 simulasi karier).
 * Pada database baru jenis ujian belum ada saat migrasi berjalan; DatabaseSeeder memuatnya setelah ExamSeeder dan CareerSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests() || ! ExamSegment::query()->exists()) {
            return;
        }

        app(QuestionBankLoader::class)->loadAll();
    }

    public function down(): void
    {
        // Isi bank soal tidak dibalik.
    }
};
