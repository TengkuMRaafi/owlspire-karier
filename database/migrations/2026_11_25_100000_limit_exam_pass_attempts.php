<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tiket kini punya jatah ujian penuh terbatas (bawaan 2x). Tiket lama yang tanpa hitungan (remaining NULL)
        // diberi jatah 2x dikurangi ujian penuh yang sudah dikerjakan sejak tiket itu dimiliki, minimal 0.
        $per = max(1, (int) config('platform.exam.pass_attempts', 2));

        DB::table('entitlements')->where('type', 'exam_pass')->whereNull('remaining')->orderBy('id')->each(function ($e) use ($per) {
            $used = DB::table('exam_attempts')
                ->where('user_id', $e->user_id)->where('exam_type_id', $e->reference_id)->where('mode', 'full')
                ->when($e->created_at, fn ($q) => $q->where('started_at', '>=', $e->created_at))
                ->count();

            DB::table('entitlements')->where('id', $e->id)->update(['remaining' => max(0, $per - $used)]);
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan: hitungan sisa dibiarkan.
    }
};
