<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('division')->index();
            $table->text('summary');
            $table->json('details')->nullable();   // does, tags, path, tren
            $table->unsignedSmallInteger('rank')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('exam_type_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('career_skill', function (Blueprint $table) {
            $table->foreignId('career_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->primary(['career_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_skill');
        Schema::dropIfExists('careers');
    }
};
