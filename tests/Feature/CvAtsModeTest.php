<?php

namespace Tests\Feature;

use App\Models\CvAnalysis;
use App\Models\CvProfile;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Careers\CvAtsKeywords;
use App\Services\Careers\CvInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Analisis CV mode ATS: kata kunci per jabatan, pemeriksaan format, dan CV biasa yang tetap sama. */
class CvAtsModeTest extends TestCase
{
    use RefreshDatabase;

    private const ATS_CV = <<<'TXT'
RAAFI
Pekanbaru, Riau | 0812-3456-7890 | raafi@example.com | github.com/raafi

RINGKASAN
Data Analyst dengan pengalaman membangun dashboard dan analisis penjualan.

PENDIDIKAN
Politeknik Caltex Riau - S1 Teknik Informatika, IPK 3,62
2023 - sekarang

PENGALAMAN KERJA
Data Analyst Intern - PT Contoh Teknologi
Jan 2024 - Des 2024
- Mengembangkan dashboard penjualan dengan Power BI yang dipakai 300 pengguna
- Mengoptimalkan query SQL sehingga waktu muat turun 40%
- Membersihkan data memakai Python dan Pandas, laporan Excel mingguan

KEAHLIAN
SQL, Microsoft Excel, Power BI, Python, Pandas, Statistik, Komunikasi
TXT;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-ATS-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);

        return $user;
    }

    public function test_jabatan_dipetakan_ke_rumpun_yang_tepat(): void
    {
        $k = new CvAtsKeywords();
        $this->assertSame('data-analyst', $k->familyFor('Data Analyst'));
        $this->assertSame('software', $k->familyFor('Backend Developer'));
        $this->assertSame('hr', $k->familyFor('HR Recruiter'));
        $this->assertSame('finance', $k->familyFor('Staff Akuntansi'));
        $this->assertNull($k->familyFor('Pawang Hujan'));
    }

    public function test_cv_yang_pas_dengan_jabatan_dapat_skor_lebih_tinggi_daripada_yang_tidak(): void
    {
        $k = new CvAtsKeywords();
        $in = (new CvInsights())->analyze(self::ATS_CV, ['SQL', 'Excel', 'Power BI', 'Python']);

        $match = $k->analyze(self::ATS_CV, 'Data Analyst', $in);
        $other = $k->analyze(self::ATS_CV, 'Customer Service', $in);

        $this->assertGreaterThan($other['coverage'], $match['coverage']);
        $this->assertGreaterThan($other['score'], $match['score']);
        $this->assertContains('SQL', array_column($match['found'], 'label'));
        $this->assertContains('Tableau', array_column($match['missing'], 'label'));
        $this->assertNotEmpty($match['checks']);
    }

    public function test_cv_dengan_banyak_simbol_dan_tanpa_judul_gagal_pemeriksaan_format(): void
    {
        $messy = "★ ☎ ✉ ◆ ◆ ◆ ► ► ✦ ✦ ❖ ❖ ✪ ✪\nsaya suka belajar ★★★ ◆◆◆ ❖❖❖ ✦✦✦ ✪✪✪\n";
        $in = (new CvInsights())->analyze($messy, []);
        $ats = (new CvAtsKeywords())->analyze($messy, 'Data Analyst', $in);

        $failed = collect($ats['checks'])->where('ok', false)->pluck('key')->all();
        $this->assertContains('plain', $failed);
        $this->assertContains('heading', $failed);
        $this->assertLessThan(40, $ats['score']);
    }

    public function test_form_cv_ats_wajib_mengisi_jabatan(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_type' => 'ats', 'cv_text' => self::ATS_CV])->assertSessionHasErrors('target_role');
        $this->assertSame(0, CvAnalysis::where('user_id', $pro->id)->count());
    }

    public function test_cv_ats_tersimpan_dan_tampil_dengan_pratinjau_tetap_ada(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_type' => 'ats', 'target_role' => 'Data Analyst', 'cv_text' => self::ATS_CV])
            ->assertRedirect('/cv')->assertSessionHas('status');

        $parsed = CvProfile::where('user_id', $pro->id)->firstOrFail()->parsed;
        $this->assertSame('ats', $parsed['mode']);
        $this->assertSame('Data Analyst', $parsed['ats']['role']);

        $row = CvAnalysis::where('user_id', $pro->id)->firstOrFail();
        $this->assertSame('ats', $row->result['mode']);
        $this->assertNotNull($row->result['ats_score']);

        $this->get('/cv')->assertOk()->assertSee('Kesiapan ATS: Data Analyst')->assertSee('Pratinjau CV-mu')->assertSee('Kata kunci Data Analyst');
        $this->get('/cv/laporan')->assertOk()->assertSee('Kesiapan ATS untuk Data Analyst');
    }

    public function test_cv_biasa_tidak_berubah_dan_tidak_menampilkan_kartu_ats(): void
    {
        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_type' => 'standard', 'cv_text' => self::ATS_CV])->assertRedirect('/cv');

        $this->assertSame('standard', CvProfile::where('user_id', $pro->id)->firstOrFail()->parsed['mode']);
        $this->get('/cv')->assertOk()->assertSee('Pratinjau CV-mu')->assertDontSee('Kesiapan ATS:');
    }
}
