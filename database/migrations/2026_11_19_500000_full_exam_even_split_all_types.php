<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Semua jenis ujian (karier, CPNS, BUMN, Polri/TNI): ujian penuh 100 soal dibagi rata antar bagian.
 * Mode coba tidak diubah (5 soal per bagian = 15 soal).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('exam_types')->pluck('id') as $typeId) {
            $ids = DB::table('exam_segments')->where('exam_type_id', $typeId)->orderBy('position')->orderBy('id')->pluck('id')->values();
            $n = $ids->count();
            if ($n === 0) {
                continue;
            }
            foreach ($ids as $i => $id) {
                DB::table('exam_segments')->where('id', $id)->update(['question_count_full' => intdiv(100, $n) + ($i < 100 % $n ? 1 : 0)]);
            }
        }
        DB::table('exam_types')->where('kind', 'career')->update(['duration_seconds' => 6000]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: jumlah soal lama berbeda per jenis ujian.
    }
};
