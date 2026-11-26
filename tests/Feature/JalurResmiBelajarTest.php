<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\Learning\JalurResmiSeeder;
use App\Services\Learning\MateriSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Jalur belajar CPNS, BUMN, dan TNI/POLRI: isi lengkap tiap topik, istilah, dan tampilan topik tanpa video. */
class JalurResmiBelajarTest extends TestCase
{
    use RefreshDatabase;

    private const JALUR = ['jalur-cpns' => 5, 'jalur-bumn' => 4, 'jalur-tni-polri' => 5];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_setiap_topik_punya_penjelasan_lengkap_dan_istilah_valid(): void
    {
        foreach (self::JALUR as $slug => $jumlah) {
            $lessons = Course::where('slug', $slug)->firstOrFail()->lessons()->orderBy('lessons.position')->get();
            $this->assertCount($jumlah, $lessons, $slug);

            foreach ($lessons as $lesson) {
                $deep = $lesson->details['deep'] ?? null;
                $this->assertNotNull($deep, "$slug {$lesson->title} belum punya penjelasan lengkap");
                $this->assertGreaterThanOrEqual(3, count($deep['sections']), $lesson->title);
                $this->assertGreaterThanOrEqual(3, count($deep['checks']), $lesson->title);
                $this->assertGreaterThanOrEqual(3, count($deep['mistakes']), $lesson->title);
                $this->assertNotEmpty($deep['summary'], $lesson->title);

                foreach ($lesson->details['terms'] ?? [] as $term) {
                    $this->assertArrayHasKey('t', $term, $lesson->title);
                    $this->assertArrayHasKey('d', $term, $lesson->title);
                }
            }
        }
    }

    public function test_pemasangan_isi_aman_diulang_dan_memperbaiki_istilah_lama(): void
    {
        $this->assertSame(0, MateriSeeder::applyDeep());

        $lesson = Course::where('slug', 'jalur-cpns')->firstOrFail()->lessons()->orderBy('lessons.position')->firstOrFail();
        $details = $lesson->details;
        $details['terms'] = [['CAT', 'Computer Assisted Test']];
        $lesson->update(['details' => $details]);

        $this->assertSame(1, JalurResmiSeeder::normalizeTerms());
        $this->assertSame([['t' => 'CAT', 'd' => 'Computer Assisted Test']], $lesson->fresh()->details['terms']);
        $this->assertSame(0, JalurResmiSeeder::normalizeTerms());
    }

    public function test_topik_dengan_video_resmi_menampilkan_pemutar_dan_penjelasan(): void
    {
        $course = Course::where('slug', 'jalur-cpns')->firstOrFail();
        $lesson = $course->lessons()->where('lessons.slug', 'topik-2')->firstOrFail();

        $this->get(route('learning.lesson', [$course, $lesson->id]))
            ->assertOk()
            ->assertSee('Penjelasan lengkap')
            ->assertSee('Istilah penting')
            ->assertSee('Bela negara')
            ->assertSee('Pohon beringin')
            ->assertSee('data-id="r4JzNI5Po_c"', false);
    }

    public function test_topik_tanpa_video_menautkan_sumber_resmi_bukan_pencarian_youtube(): void
    {
        $course = Course::where('slug', 'jalur-bumn')->firstOrFail();
        $lesson = $course->lessons()->where('lessons.slug', 'topik-2')->firstOrFail();
        $this->assertTrue($lesson->is_free_preview);

        $this->get(route('learning.lesson', [$course, $lesson->id]))
            ->assertOk()
            ->assertSee('Buka sumber resmi')
            ->assertSee('Sumber resmi topik ini')
            ->assertSee('Pasal 33')
            ->assertDontSee('Cari videonya di YouTube')
            ->assertSee('Simulasi BUMN');
    }

    public function test_materi_tidak_menimpa_isi_yang_disunting_admin(): void
    {
        $lesson = Course::where('slug', 'jalur-tni-polri')->firstOrFail()->lessons()->orderBy('lessons.position')->firstOrFail();
        $details = $lesson->details;
        $details['deep']['sections'] = [['heading' => 'Disunting admin', 'paragraphs' => ['x']]];
        $lesson->update(['details' => $details]);

        MateriSeeder::applyDeep();

        $this->assertSame('Disunting admin', Lesson::find($lesson->id)->details['deep']['sections'][0]['heading']);
    }
}
