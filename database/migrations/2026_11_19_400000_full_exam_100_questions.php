<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ujian penuh simulasi karier: dari 18 soal jadi 100 soal (34/33/33 antar bagian, diacak dari bank),
 * dengan durasi 100 menit. Simulasi seleksi (CPNS, BUMN, Polri) tidak diubah di database; totalnya dibatasi 100
 * saat soal dipilih (lihat App\Services\Exam\FullExamPlan).
 */
return new class extends Migration
{
    public function up(): void
    {
        $split = [34, 33, 33];

        DB::table('exam_types')->where('kind', 'career')->update(['duration_seconds' => 6000]);

        $typeIds = DB::table('exam_types')->where('kind', 'career')->pluck('id');
        foreach ($typeIds as $typeId) {
            $segments = DB::table('exam_segments')->where('exam_type_id', $typeId)->orderBy('position')->orderBy('id')->pluck('id')->values();
            foreach ($segments as $i => $segmentId) {
                DB::table('exam_segments')->where('id', $segmentId)->update(['question_count_full' => $split[$i] ?? 33]);
            }
        }
    }

    public function down(): void
    {
        DB::table('exam_types')->where('kind', 'career')->update(['duration_seconds' => 1800]);
        DB::table('exam_segments')->whereIn('exam_type_id', DB::table('exam_types')->where('kind', 'career')->select('id'))->update(['question_count_full' => 6]);
    }
};
