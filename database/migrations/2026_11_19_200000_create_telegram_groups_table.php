<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_groups', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('title')->nullable();
            $table->string('zone')->default('wib');          // wib | wita | wit
            $table->string('kind')->default('kerja');         // kerja | magang
            $table->json('positions')->nullable();            // kosong = pakai bawaan di config/platform.php
            $table->json('skills')->nullable();
            $table->json('locations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('last_slot')->nullable();          // mis. 2026-11-20-06, supaya tidak terkirim dobel
            $table->json('sent_urls')->nullable();            // lowongan yang sudah dikirim, agar tidak diulang
            $table->unsignedTinyInteger('fail_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_groups');
    }
};
