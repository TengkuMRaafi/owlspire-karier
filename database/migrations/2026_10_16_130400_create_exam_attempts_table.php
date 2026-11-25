<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token')->nullable()->index();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();
            $table->string('mode')->default('trial'); // trial | full
            $table->timestamp('started_at');
            $table->timestamp('deadline_at')->nullable(); // waktu resmi dihitung server
            $table->timestamp('finished_at')->nullable();
            $table->json('score')->nullable();
            $table->unsignedInteger('total_score')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
