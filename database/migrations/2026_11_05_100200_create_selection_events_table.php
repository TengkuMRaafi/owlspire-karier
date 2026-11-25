<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selection_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('category', 20)->index();   // cpns | bumn | polri | tni | kedinasan | lainnya
            $table->string('stage', 60)->nullable();    // mis. Pendaftaran, SKD, Wawancara
            $table->date('starts_on')->index();
            $table->date('ends_on')->nullable();
            $table->string('source_url', 500)->nullable(); // tautan pengumuman resmi
            $table->text('note')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selection_events');
    }
};
