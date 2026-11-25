<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ExamSeeder;
use App\Models\ExamSegment;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_tidak_boleh_masuk_panel_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_bisa_membuka_semua_halaman_panel(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach (['/admin', '/admin/soal', '/admin/soal/tambah', '/admin/soal/impor', '/admin/jenis-ujian', '/admin/hasil-ujian', '/admin/pesanan', '/admin/pengguna', '/admin/pengaturan'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_admin_menambah_soal(): void
    {
        $this->seed(ExamSeeder::class);
        $segment = ExamSegment::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/admin/soal', [
            'segment_id' => $segment->id,
            'body' => 'Ibu kota Indonesia adalah?',
            'options' => ['A' => 'Bandung', 'B' => 'Jakarta', 'C' => '', 'D' => '', 'E' => ''],
            'answer' => 'B',
            'is_trial' => '1',
        ])->assertRedirect('/admin/soal');

        $q = Question::where('body', 'Ibu kota Indonesia adalah?')->firstOrFail();
        $this->assertSame(['A' => 'Bandung', 'B' => 'Jakarta'], $q->options);
        $this->assertTrue($q->is_trial);
    }

    public function test_kunci_jawaban_harus_salah_satu_pilihan_terisi(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/admin/soal', [
            'segment_id' => ExamSegment::value('id'),
            'body' => 'Soal ngawur',
            'options' => ['A' => 'x', 'B' => 'y', 'C' => '', 'D' => '', 'E' => ''],
            'answer' => 'D',
        ])->assertSessionHasErrors('answer');
    }

    public function test_impor_json_format_lama(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $before = Question::count();

        $json = json_encode([['examType' => 'cpns', 'segmentId' => 'twk', 'q' => 'Soal impor uji coba', 'opts' => ['A. satu', 'B. dua', 'C. tiga'], 'ans' => 'B', 'exp' => 'karena dua', 'isTrial' => false]]);
        $file = UploadedFile::fake()->createWithContent('soal.json', $json);

        $this->post('/admin/soal/impor', ['file' => $file])->assertSessionHas('status');

        $this->assertSame($before + 1, Question::count());
        $this->assertSame(['A' => 'satu', 'B' => 'dua', 'C' => 'tiga'], Question::where('body', 'Soal impor uji coba')->value('options'));
    }
}
