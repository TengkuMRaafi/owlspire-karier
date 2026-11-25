<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wawancara berbasis percakapan: seluruh obrolan disimpan di transcript, penilaian dibuat sekali di akhir (report).
        // Kolom questions dan answers lama tetap ada supaya riwayat sesi lama masih bisa dibuka.
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->json('transcript')->nullable();
            $table->json('report')->nullable();
            $table->text('context')->nullable(); // lowongan atau catatan dari pengguna (opsional)
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['transcript', 'report', 'context']);
        });
    }
};
