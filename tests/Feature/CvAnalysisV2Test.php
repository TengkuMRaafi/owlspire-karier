<?php

namespace Tests\Feature;

use App\Models\CvAnalysis;
use App\Models\CvProfile;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Skill;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Careers\CvInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Analisis CV v2: skor kualitas, riwayat, pencocokan lowongan, laporan, dan penanganan data. */
class CvAnalysisV2Test extends TestCase
{
    use RefreshDatabase;

    private const GOOD = <<<'TXT'
TENGKU RAAFI
Pekanbaru, Riau | 0812-3456-7890 | raafi@example.com | linkedin.com/in/raafi | github.com/raafi

RINGKASAN
Mahasiswa Teknik Informatika yang berpengalaman membangun aplikasi web dan analisis data.

PENDIDIKAN
Politeknik Caltex Riau - S1 Teknik Informatika, IPK 3,62
2023 - sekarang

PENGALAMAN KERJA
Data Analyst Intern - PT Contoh Teknologi
Jan 2024 - Des 2024
- Mengembangkan dashboard penjualan dengan Power BI yang dipakai 300 pengguna
- Mengoptimalkan query SQL sehingga waktu muat turun 40%
- Menganalisis data pelanggan memakai Python dan Excel
Freelance Developer
2022 - 2023
- Membangun 5 website untuk klien UMKM

KEAHLIAN
SQL, Excel, Power BI, Python, Git

SERTIFIKAT
Dicoding Belajar Dasar AI
TXT;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // paket, skill, dan karier
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-CV-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);

        return $user;
    }

    public function test_cv_lengkap_dapat_skor_tinggi_dan_cv_buruk_skor_rendah_dengan_saran(): void
    {
        $in = new CvInsights();
        $good = $in->analyze(self::GOOD, ['SQL', 'Excel', 'Power BI', 'Python', 'Git']);
        $this->assertGreaterThanOrEqual(70, $good['score']);
        $this->assertSame(3.0, $good['experience']['years']);   // 2024 (12 bln) + 2022-2023 (24 bln); rentang kuliah tidak dihitung
        $this->assertSame('S1', $good['education']['level']);
        $this->assertSame(3.62, $good['education']['gpa']);
        $this->assertTrue($good['contact']['email'] && $good['contact']['phone'] && $good['contact']['linkedin']);
        $this->assertTrue($good['sections']['pengalaman']['found'] && $good['sections']['sertifikat']['found']);
        $this->assertGreaterThanOrEqual(2, $good['metrics']['numbers']);

        $bad = $in->analyze('saya orang rajin dan jujur. bertanggung jawab atas pekerjaan. agama islam, tinggi badan 170', []);
        $this->assertLessThan(25, $bad['score']);
        $titles = collect($bad['issues'])->pluck('title')->implode('|');
        $this->assertStringContainsString('Email tidak terbaca', $titles);
        $this->assertStringContainsString('Ada frasa pasif', $titles);
        $this->assertStringContainsString('data pribadi', $titles);
        $this->assertSame('high', $bad['issues'][0]['level']); // masalah penting di urutan teratas
    }

    public function test_analisis_menyimpan_riwayat_skor_dan_menampilkannya(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_text' => 'Analis data muda. Keahlian: SQL dan Excel untuk laporan.', 'label' => 'Versi awal'])->assertRedirect('/cv');
        $this->post('/cv', ['cv_text' => self::GOOD, 'label' => 'Versi baru'])->assertRedirect('/cv')->assertSessionHas('status');

        $rows = CvAnalysis::where('user_id', $pro->id)->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertGreaterThan($rows[0]->score, $rows[1]->score);
        $this->assertSame('Versi baru', $rows[1]->label);
        $this->assertStringNotContainsString('Pekanbaru', json_encode($rows[1]->result)); // riwayat tanpa teks CV

        // Hasil dipadatkan: tiga kartu angka, pratinjau CV bertanda panah, dan karier yang cocok. Tidak ada kartu skor, riwayat, atau daftar masalah.
        $this->get('/cv')->assertOk()->assertSee('Pratinjau CV-mu')->assertSee('Karier yang paling cocok')->assertSee('Skill terbaca')
            ->assertSee('Kecocokan tertinggi')->assertSee('cvp-note', false)
            ->assertDontSee('Skor kualitas CV')->assertDontSee('Perkembangan skor CV')->assertDontSee('Yang perlu diperbaiki')->assertDontSee('Cocokkan dengan satu lowongan');
    }

    public function test_riwayat_dibatasi_dan_level_skill_manual_tidak_tertimpa(): void
    {
        $pro = $this->premiumUser();
        $sql = Skill::where('name', 'SQL')->firstOrFail();
        DB::table('user_skills')->insert(['user_id' => $pro->id, 'skill_id' => $sql->id, 'level' => 4, 'source' => 'manual', 'created_at' => now(), 'updated_at' => now()]);

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)->actingAs($pro);
        for ($i = 0; $i < 14; $i++) {
            $this->post('/cv', ['cv_text' => 'Keahlian: SQL, Excel, Power BI. Pengalaman analis data selama dua tahun.']);
        }
        $this->assertSame(12, CvAnalysis::where('user_id', $pro->id)->count());
        $this->assertSame(4, (int) DB::table('user_skills')->where('user_id', $pro->id)->where('skill_id', $sql->id)->value('level')); // tidak di-reset ke 1
        $this->assertSame('manual', DB::table('user_skills')->where('user_id', $pro->id)->where('skill_id', $sql->id)->value('source'));
    }

    public function test_skill_dari_cv_dilepas_saat_cv_baru_tidak_memuatnya_dan_saat_dihapus(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_text' => 'Keahlian: SQL, Excel, Power BI dan Python untuk analisis data.']);
        $skillCount = fn () => DB::table('user_skills')->where('user_id', $pro->id)->where('source', 'cv')->count();
        $this->assertGreaterThanOrEqual(3, $skillCount());

        $this->post('/cv', ['cv_text' => 'Keahlian: Excel. Pengalaman administrasi perkantoran di sekolah.']);
        $names = DB::table('user_skills')->join('skills', 'skills.id', '=', 'user_skills.skill_id')->where('user_id', $pro->id)->pluck('skills.name');
        $this->assertTrue($names->contains('Excel'));
        $this->assertFalse($names->contains('Python'));

        $this->delete('/cv')->assertRedirect();
        $this->assertSame(0, $skillCount());
        $this->assertSame(0, CvAnalysis::where('user_id', $pro->id)->count());
        $this->assertSame(0, CvProfile::where('user_id', $pro->id)->count());
    }

    public function test_teks_cv_disimpan_terenkripsi_tetapi_terbaca_lewat_model(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_text' => self::GOOD]);

        $raw = DB::table('cv_profiles')->where('user_id', $pro->id)->value('raw_text');
        $this->assertStringNotContainsString('raafi@example.com', $raw);
        $this->assertStringContainsString('raafi@example.com', CvProfile::where('user_id', $pro->id)->firstOrFail()->raw_text);

        // Data lama (teks biasa) tetap terbaca.
        DB::table('cv_profiles')->where('user_id', $pro->id)->update(['raw_text' => 'teks lama tanpa enkripsi']);
        $this->assertSame('teks lama tanpa enkripsi', CvProfile::where('user_id', $pro->id)->firstOrFail()->raw_text);
    }

    public function test_cocokkan_cv_dengan_lowongan(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_text' => self::GOOD]);

        $job = 'Dicari Data Analyst. Kualifikasi: menguasai SQL, Power BI, Tableau, dan Python. Mampu membuat dashboard dan laporan penjualan bulanan, melakukan analisis data pelanggan.';
        $this->post('/cv/cocokkan', ['job' => $job, 'job_title' => 'Data Analyst di PT X'])->assertRedirect();
        $m = session('job_match');
        $this->assertContains('SQL', $m['matched_skills']);
        $this->assertContains('Power BI', $m['matched_skills']);
        $this->assertContains('Tableau', $m['missing_skills']);
        $this->assertGreaterThan(30, $m['score']);
        $this->assertLessThan(100, $m['score']);

        $this->post('/cv/cocokkan', ['job' => 'pendek'])->assertSessionHasErrors('job');
    }

    public function test_laporan_cetak_hanya_untuk_premium_yang_punya_analisis(): void
    {
        $free = User::factory()->create();
        $this->actingAs($free)->post('/cv/cocokkan', ['job' => str_repeat('lowongan data analyst ', 5)])->assertForbidden();
        $this->get('/cv/laporan')->assertForbidden();

        $pro = $this->premiumUser();
        $this->actingAs($pro)->get('/cv/laporan')->assertNotFound();
        $this->post('/cv', ['cv_text' => self::GOOD]);
        $this->get('/cv/laporan')->assertOk()->assertSee('Laporan Analisis CV')->assertSee('Rincian skor');
    }

    public function test_docx_terlalu_besar_ditolak_dan_gambar_tanpa_ocr_ditolak_halus(): void
    {
        $pro = $this->premiumUser();

        $path = tempnam(sys_get_temp_dir(), 'cv').'.docx';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<w:t>'.str_repeat('a', 9 * 1024 * 1024).'</w:t>');
        $zip->close();
        $this->actingAs($pro)->post('/cv', ['cv_file' => new UploadedFile($path, 'besar.docx', null, null, true)])->assertSessionHasErrors('cv_file');

        // Bila OCR tidak terpasang, gambar tidak diterima oleh validasi (tidak error 500).
        if (! app(\App\Services\Careers\CvAnalyzer::class)->ocrAvailable()) {
            $img = UploadedFile::fake()->image('cv.png');
            $this->post('/cv', ['cv_file' => $img])->assertSessionHasErrors('cv_file');
        }
    }

    public function test_menu_perbaikan_cv_sudah_dihapus_dan_alamat_lama_dialihkan(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->get('/cv')->assertOk()->assertSee('Analisis CV')->assertDontSee('Perbaikan CV dan surat lamaran')->assertDontSee('Perbaiki dengan AI');
        $this->get('/dashboard/cv-surat')->assertRedirect('/cv');
    }

    public function test_analisis_cv_dibatasi_per_hari_dan_input_salah_tidak_memakan_jatah(): void
    {
        config(['platform.ai_tools.cv_analysis.premium_daily' => 2]);
        $pro = $this->premiumUser();

        $this->actingAs($pro)->post('/cv', ['cv_text' => 'terlalu pendek'])->assertSessionHasErrors('cv_text'); // gagal validasi: jatah utuh
        $this->post('/cv', ['cv_text' => self::GOOD])->assertSessionHasNoErrors();
        $this->post('/cv', ['cv_text' => self::GOOD])->assertSessionHasNoErrors();
        $this->post('/cv', ['cv_text' => self::GOOD])->assertSessionHasErrors('cv_file'); // jatah 2 habis
        $this->get('/cv')->assertOk()->assertSee('Sisa jatah analisis hari ini: 0/2');
    }

    public function test_pratinjau_cv_menandai_bagian_bagus_dan_yang_perlu_diperbaiki(): void
    {
        $in = new CvInsights();
        $names = ['SQL', 'Excel', 'Power BI', 'Python', 'Git'];
        $pv = $in->preview(self::GOOD, $in->analyze(self::GOOD, $names), $names, 'Data Analyst', ['Tableau']);

        $keys = array_column($pv['blocks'], 'key');
        $this->assertSame(['kontak', 'ringkasan', 'pendidikan', 'pengalaman', 'skill', 'sertifikat'], $keys); // urutan mengikuti CV asli
        $byKey = collect($pv['blocks'])->keyBy('key');
        $this->assertSame('good', $byKey['kontak']['notes'][0]['t']);
        $this->assertSame('good', $byKey['ringkasan']['notes'][0]['t']);
        $this->assertSame('weak', $byKey['pengalaman']['notes'][0]['t']); // baru 2 poin ber-angka: kurang menonjol
        $this->assertContains('good', array_column($byKey['pengalaman']['lines'], 'm')); // baris ber-angka ditandai hijau
        $this->assertStringContainsString('Tableau', collect($byKey['skill']['notes'])->pluck('p')->implode(' ')); // celah skill karier teratas
        $this->assertGreaterThan(0, $pv['counts']['good']);

        $bad = 'saya orang rajin dan jujur. bertanggung jawab atas pekerjaan. agama islam, tinggi badan 170';
        $pb = $in->preview($bad, $in->analyze($bad, []), [], null, []);
        $this->assertSame('isi', $pb['blocks'][0]['key']);          // tanpa judul bagian: satu blok "Isi CV"
        $this->assertSame('bad', $pb['blocks'][0]['notes'][0]['t']);
        $this->assertContains('weak', array_column($pb['blocks'][0]['lines'], 'm'));
        $this->assertGreaterThan(0, $pb['counts']['bad']);

        $partial = "Nama Saya\nsaya@example.com\n\nKEAHLIAN\nSQL, Excel";
        $pp = $in->preview($partial, $in->analyze($partial, ['SQL', 'Excel']), ['SQL', 'Excel'], null, []);
        $ghosts = collect($pp['blocks'])->where('ghost', true)->pluck('key')->all();
        $this->assertContains('pengalaman', $ghosts);
        $this->assertContains('pendidikan', $ghosts);
        $this->assertNotContains('skill', $ghosts);
    }

}
