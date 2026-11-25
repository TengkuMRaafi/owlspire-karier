<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ulangi simulasi: percobaan baru yang memakai soal yang sama dengan percobaan sebelumnya.
        // Penanda ini menjaga peringkat tetap adil (ulangan dengan soal yang sama tidak ikut dihitung).
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedBigInteger('retake_of')->nullable()->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('retake_of');
        });
    }
};
