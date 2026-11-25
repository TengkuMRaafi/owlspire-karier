<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fitur kartu "Ulangi soal" sudah diganti "Ulangi simulasi"; antrean kartunya tidak dipakai lagi.
        Schema::dropIfExists('wrong_question_reviews');
    }

    public function down(): void
    {
        Schema::create('wrong_question_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->float('ease')->default(2.5);
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->unsignedSmallInteger('repetitions')->default(0);
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'question_id']);
        });
    }
};
