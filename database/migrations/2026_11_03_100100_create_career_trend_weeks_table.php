<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu karier pada satu minggu. week_start = tanggal Minggu (zona waktu jadwal).
        Schema::create('career_trend_weeks', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')->index();
            $table->foreignId('career_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('jobs')->default(0);     // lowongan dari API sumber resmi
            $table->unsignedInteger('sims')->default(0);     // simulasi selesai
            $table->unsignedInteger('targets')->default(0);  // pengguna yang menargetkan karier ini
            $table->double('score')->default(0);
            $table->unsignedSmallInteger('position');
            $table->json('per_source')->nullable();          // rincian lowongan per sumber
            $table->timestamps();
            $table->unique(['week_start', 'career_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_trend_weeks');
    }
};
