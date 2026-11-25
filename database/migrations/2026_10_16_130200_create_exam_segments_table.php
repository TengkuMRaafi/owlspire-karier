<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();
            $table->string('code'); // twk | tiu | tkp
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('question_count_trial')->default(5);
            $table->unsignedSmallInteger('question_count_full')->default(30);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_segments');
    }
};
