<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tiket simulasi kini sekali beli, terbuka seterusnya (per jenis ujian). Tiket lama yang masih punya
        // hitungan sisa dijadikan akses tanpa batas pemakaian: remaining NULL = tidak dihitung.
        DB::table('entitlements')->where('type', 'exam_pass')->update(['remaining' => null]);

        // Rekam aktivitas pengerjaan: kapan soal pertama kali dijawab dan berapa kali jawabannya diubah.
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->timestamp('first_answered_at')->nullable()->after('answered_at');
            $table->unsignedSmallInteger('changes')->default(0)->after('first_answered_at');
        });

        // Perangkat yang dipakai mengerjakan (dipotong), membantu menelusuri bila ada sengketa hasil.
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('user_agent', 255)->nullable()->after('retake_of');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempt_answers', function (Blueprint $table) {
            $table->dropColumn(['first_answered_at', 'changes']);
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
