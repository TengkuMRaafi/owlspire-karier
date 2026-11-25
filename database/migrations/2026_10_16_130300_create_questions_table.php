<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('segment_id')->constrained('exam_segments')->cascadeOnDelete();
            $table->string('topic')->nullable()->index();
            $table->text('body');
            $table->json('options');            // {"A": "...", "B": "..."}
            $table->string('answer', 5); // kunci jawaban, TIDAK boleh dikirim ke browser sebelum
            $table->text('explanation')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->unsignedTinyInteger('difficulty')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
