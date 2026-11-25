<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat analisis CV (tanpa teks CV, hanya hasil). Dipakai untuk grafik perkembangan dan perbandingan versi.
        Schema::create('cv_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80)->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('result')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        // Dari mana skill di profil berasal: 'cv' (hasil analisis, bisa ikut dihapus) atau 'manual'.
        Schema::table('user_skills', function (Blueprint $table) {
            $table->string('source', 12)->default('manual');
        });
    }

    public function down(): void
    {
        Schema::table('user_skills', function (Blueprint $table) {
            $table->dropColumn('source');
        });
        Schema::dropIfExists('cv_analyses');
    }
};
