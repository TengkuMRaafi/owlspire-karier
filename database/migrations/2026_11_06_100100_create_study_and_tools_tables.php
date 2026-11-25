<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Target harian, tanggal ujian, dan pengingat (satu baris per pengguna).
        Schema::create('study_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('daily_goal')->default(5);
            $table->foreignId('exam_type_id')->nullable()->constrained('exam_types')->nullOnDelete();
            $table->date('exam_date')->nullable();
            $table->string('remind_channel', 10)->default('none'); // none | email | telegram
            $table->string('remind_time', 5)->default('19:00');
            $table->date('last_reminded_on')->nullable();
            $table->timestamps();
        });

        // Aktivitas belajar per hari, dasar streak.
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedSmallInteger('reviews')->default(0);
            $table->unsignedSmallInteger('exams')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'day']);
        });

        // Penautan akun ke bot Telegram (pengingat belajar dan alert lowongan).
        Schema::create('telegram_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('chat_id', 40)->nullable();
            $table->string('link_code', 16)->nullable()->unique();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();
        });

        // Simulasi wawancara teks.
        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 120);
            $table->string('kind', 20);
            $table->string('status', 10)->default('running'); // running | done
            $table->json('questions');
            $table->json('answers')->nullable();
            $table->unsignedTinyInteger('total_score')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        // Pemakaian fitur AI per hari (kuota Gratis dan Premium).
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 20);
            $table->date('day');
            $table->unsignedSmallInteger('used')->default(0);
            $table->unique(['user_id', 'feature', 'day']);
        });

        // Pelacak lamaran: tautan, lokasi, dan tanggal tindak lanjut untuk lowongan dari pencarian (yang tidak disimpan di job_listings).
        Schema::table('job_applications', function (Blueprint $table) {
            $table->text('url')->nullable();
            $table->string('location')->nullable();
            $table->date('follow_up_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['url', 'location', 'follow_up_on']);
        });
        Schema::dropIfExists('ai_usages');
        Schema::dropIfExists('interview_sessions');
        Schema::dropIfExists('telegram_links');
        Schema::dropIfExists('daily_activities');
        Schema::dropIfExists('study_profiles');
    }
};
