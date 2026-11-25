<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Services\Exam\QuestionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Deskripsi detail jalur, isi tiap topik, dan soal simulasi yang disusun dari materi jalur belajar. */
class MateriBelajarTest extends TestCase
{
    use RefreshDatabase;

    private const SOAL = 'seeders/data/bank-soal/materi-belajar.json';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_jalur_menampilkan_deskripsi_detail_dan_ringkasan_topik(): void
    {
        $course = Course::where('slug', 'jalur-soc-analyst')->firstOrFail();

        $this->get(route('learning.show', $course))
            ->assertOk()
            ->assertSee('Tentang jalur ini')
            ->assertSee('Analis SOC (Security Operations Center)')
            ->assertSee('Dasar jaringan dan Linux');
    }

    public function test_halaman_topik_menampilkan_poin_istilah_dan_latihan(): void
    {
        $course = Course::where('slug', 'jalur-soc-analyst')->firstOrFail();
        $lesson = $course->lessons()->orderBy('lessons.position')->firstOrFail();

        $this->get(route('learning.lesson', [$course, $lesson->id]))
            ->assertOk()
            ->assertSee('Yang kamu pelajari')
            ->assertSee('Istilah penting')
            ->assertSee('Latihan mandiri');
    }

    public function test_soal_materi_valid_dan_terhubung_ke_topik_jalur(): void
    {
        $rows = json_decode((string) file_get_contents(database_path(self::SOAL)), true);
        $this->assertCount(120, $rows);

        foreach ($rows as $r) {
            $this->assertCount(4, $r['opts']);
            $this->assertArrayHasKey($r['ans'], $r['opts']);
            $this->assertStringStartsWith('Materi: ', $r['topic']);
        }

        $importer = app(QuestionImporter::class);
        $result = $importer->import($importer->parseJson((string) file_get_contents(database_path(self::SOAL))));
        $this->assertSame(0, $result['skipped'], implode(' | ', $result['errors']));
        $this->assertSame(120, $result['created']);

        $course = Course::where('slug', 'jalur-soc-analyst')->firstOrFail();
        $lesson = $course->lessons()->orderBy('lessons.position')->firstOrFail();
        $this->get(route('learning.lesson', [$course, $lesson->id]))
            ->assertOk()
            ->assertSee('2 soal dari topik ini ada di simulasi');
    }
}
