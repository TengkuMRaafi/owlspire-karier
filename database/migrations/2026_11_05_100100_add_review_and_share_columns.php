<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ulangi soal salah: kapan kartu terakhir dijawab (untuk batas harian Gratis).
        Schema::table('wrong_question_reviews', function (Blueprint $table) {
            $table->timestamp('last_reviewed_at')->nullable()->after('due_at');
        });

        // Kartu hasil yang bisa dibagikan: tautan publik hanya ada bila pemilik hasil membuatnya.
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('share_token', 40)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn('share_token');
        });

        Schema::table('wrong_question_reviews', function (Blueprint $table) {
            $table->dropColumn('last_reviewed_at');
        });
    }
};
