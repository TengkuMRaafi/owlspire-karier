<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', fn (Blueprint $t) => $t->unsignedBigInteger('career_id')->nullable()->index());

        Schema::table('lessons', function (Blueprint $table) {
            $table->string('provider')->nullable();            // youtube | link
            $table->string('video_id', 20)->nullable();
            $table->string('license')->nullable();             // mis. Creative Commons Attribution (CC BY)
            $table->string('credit_name')->nullable();         // nama kreator / kanal
            $table->text('source_url')->nullable();
            $table->boolean('is_approved')->default(true)->index();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', fn (Blueprint $t) => $t->dropColumn(['provider', 'video_id', 'license', 'credit_name', 'source_url', 'is_approved']));
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn('career_id'));
    }
};
