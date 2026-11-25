<?php

namespace Tests\Feature;

use App\Services\Exam\QuestionImporter;
use Tests\TestCase;

/** Importer mengenali ekspor bank soal CI ({exam_type, segments}) selain daftar soal biasa. */
class QuestionBankFormatTest extends TestCase
{
    public function test_ekspor_ci_diubah_menjadi_baris_dengan_tipe_dan_segmen(): void
    {
        $json = json_encode(['exam_type' => 'cpns', 'segments' => ['twk' => ['segment_id' => 'twk', 'soal' => [
            ['uid' => 'a1', 'question' => 'Soal?', 'options' => ['A. satu', 'B. dua'], 'answer' => 'A', 'explanation' => 'x'],
        ]]]]);

        $rows = app(QuestionImporter::class)->parseJson($json);

        $this->assertCount(1, $rows);
        $this->assertSame('cpns', $rows[0]['examType']);
        $this->assertSame('twk', $rows[0]['segmentId']);
        $this->assertSame(['A' => 'satu', 'B' => 'dua'], app(QuestionImporter::class)->normalizeOptions($rows[0]['options']));
    }
}
