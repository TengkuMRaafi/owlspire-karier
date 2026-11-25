<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_source_id')->constrained('job_sources')->cascadeOnDelete();
            $table->string('external_id');
            $table->string('title');
            $table->string('company');
            $table->string('location')->nullable();
            $table->boolean('remote')->default(false);
            $table->string('kind')->default('kerja'); // kerja | magang
            $table->string('division')->nullable()->index();
            $table->text('url');
            $table->text('logo_url')->nullable();
            $table->longText('description')->nullable();
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['job_source_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
