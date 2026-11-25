<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Plan;
use App\Services\Billing\EntitlementService;
use App\Models\Career;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Lesson;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CareerPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-T-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);

        return $user;
    }

    public function test_data_karier_lengkap_15_karier_dan_15_soal_gratis_masing_masing(): void
    {
        $this->assertSame(15, Career::count());
        foreach (Career::with('examType.segments')->get() as $c) {
            $segIds = $c->examType->segments->pluck('id');
            $this->assertSame(15, Question::whereIn('segment_id', $segIds)->where('is_trial', true)->count(), $c->slug.' gratis');
            $this->assertSame(12, Question::whereIn('segment_id', $segIds)->where('is_trial', false)->count(), $c->slug.' premium');
            $this->assertGreaterThanOrEqual(6, $c->skills()->count());
        }
        // Kunci jawaban selalu salah satu opsi.
        Question::all()->each(fn ($q) => $this->assertArrayHasKey($q->answer, $q->options));
    }

    public function test_landing_menampilkan_roadmap_dan_karier(): void
    {
        $this->get('/')->assertOk()->assertSee('Roadmap perjalananmu')->assertSee('AI Engineer')->assertSee('Kenali dirimu')->assertSee('Premium');
    }

    public function test_halaman_karier_tes_arah_dan_belajar_terbuka_untuk_tamu(): void
    {
        $this->get('/simulasi?q=data')->assertOk()->assertSee('Data Analyst');
        $this->get('/karier/ai-engineer')->assertOk()->assertSee('Simulasi AI Engineer')->assertSee('Coba gratis')->assertSee('LinkedIn');
        $this->get('/arah-karier')->assertOk()->assertSee('Coba simulasi')->assertSee('Cari arah kariermu');
        $this->get('/belajar')->assertOk()->assertSee('Buka jalur');
        $this->get('/simulasi?q=data')->assertOk()->assertSee('Data Analyst');
    }

    public function test_tamu_bisa_simulasi_gratis_15_soal_karier(): void
    {
        $this->post('/simulasi/karier-data-analyst/mulai', ['mode' => 'trial'])->assertRedirect();
        $attempt = ExamAttempt::firstOrFail();
        $this->assertSame(15, $attempt->answers()->count());
        // Mode coba hanya memakai soal gratis.
        $this->assertSame(0, $attempt->answers()->whereHas('question', fn ($q) => $q->where('is_trial', false))->count());
    }

    public function test_ujian_lanjutan_karier_khusus_premium_dan_memakai_soal_sulit(): void
    {
        $free = User::factory()->create();
        $this->actingAs($free)->post('/simulasi/karier-data-analyst/mulai', ['mode' => 'full'])->assertSessionHas('error');
        $this->assertSame(0, ExamAttempt::count());

        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/simulasi/karier-data-analyst/mulai', ['mode' => 'full'])->assertRedirect();
        $attempt = ExamAttempt::firstOrFail();
        $this->assertSame(27, $attempt->answers()->count()); // jatah 100 soal > isi bank, jadi seluruh bank (15 gratis + 12 lanjut) terpakai
        $hard = $attempt->answers()->whereHas('question', fn ($q) => $q->where('is_trial', false))->count();
        $this->assertSame(12, $hard); // seluruh soal level lanjut ikut
    }

    public function test_cv_gratis_ditolak_premium_bisa_dan_skill_terbaca(): void
    {
        $text = 'Saya analis data berpengalaman. Keahlian: SQL, Excel, Power BI, Python, statistik dan Data Visualization. Pernah membuat dashboard penjualan.';

        $free = User::factory()->create();
        $this->actingAs($free)->get('/cv')->assertOk()->assertSee('khusus Premium');
        $this->actingAs($free)->post('/cv', ['cv_text' => $text])->assertForbidden();

        $pro = $this->premiumUser();
        $this->actingAs($pro)->post('/cv', ['cv_text' => $text])->assertRedirect('/cv');
        $this->get('/cv')->assertOk()->assertSee('Data Analyst')->assertSee('Skill terbaca');

        $profile = \App\Models\CvProfile::where('user_id', $pro->id)->firstOrFail();
        $names = collect($profile->parsed['skills'])->pluck('name');
        $this->assertTrue($names->contains('SQL') && $names->contains('Power BI') && $names->contains('Python'));
        $top = $profile->parsed['matches'][0];
        $this->assertSame('data-analyst', Career::find($top['career_id'])->slug);

        $this->delete('/cv')->assertRedirect();
        $this->assertSame(0, \App\Models\CvProfile::where('user_id', $pro->id)->count());
    }

    public function test_cv_docx_dan_txt_terbaca(): void
    {
        $pro = $this->premiumUser();
        $txt = UploadedFile::fake()->createWithContent('cv.txt', 'Pengalaman rekrutmen dan wawancara, payroll, onboarding serta HRIS selama 3 tahun.');
        $this->actingAs($pro)->post('/cv', ['cv_file' => $txt])->assertRedirect('/cv');
        $names = collect(\App\Models\CvProfile::first()->parsed['skills'])->pluck('name');
        $this->assertTrue($names->contains('Recruitment') && $names->contains('Payroll'));

        $path = tempnam(sys_get_temp_dir(), 'cv').'.docx';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<w:document><w:body><w:p><w:r><w:t>Menguasai Figma, wireframing dan usability testing untuk produk digital</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        $docx = new UploadedFile($path, 'cv.docx', null, null, true);
        $this->post('/cv', ['cv_file' => $docx])->assertRedirect('/cv');
        $names = collect(\App\Models\CvProfile::first()->parsed['skills'])->pluck('name');
        $this->assertTrue($names->contains('Figma') && $names->contains('Wireframing'));
    }

    public function test_belajar_dua_topik_gratis_sisanya_premium_dan_progres_tersimpan(): void
    {
        $course = Career::where('slug', 'data-analyst')->firstOrFail()->course;
        $lessons = Lesson::whereIn('section_id', $course->sections()->pluck('id'))->orderBy('position')->get();
        $this->assertCount(4, $lessons);

        $user = User::factory()->create();
        $this->actingAs($user)->get("/belajar/{$course->slug}/{$lessons[0]->id}")->assertOk()->assertSee('Owlspire Belajar')->assertSee('data-lp', false)->assertSee('Diputar dari YouTube');
        $this->get("/belajar/{$course->slug}/{$lessons[3]->id}")->assertOk()->assertSee('jalur lengkap Premium');
        $this->post("/belajar/{$course->slug}/{$lessons[3]->id}/selesai")->assertSessionHas('error');
        $this->post("/belajar/{$course->slug}/{$lessons[0]->id}/selesai")->assertSessionHas('status');

        $pro = $this->premiumUser();
        $this->actingAs($pro)->get("/belajar/{$course->slug}/{$lessons[3]->id}")->assertOk()->assertDontSee('jalur lengkap Premium');
    }

    public function test_video_youtube_hanya_tampil_bila_disetujui_dengan_kredit_dan_lisensi(): void
    {
        $course = Career::where('slug', 'data-analyst')->firstOrFail()->course;
        $section = $course->sections()->first();
        $l = Lesson::create(['section_id' => $section->id, 'title' => 'Video SQL', 'slug' => 'yt-abc', 'type' => 'video', 'provider' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ', 'license' => 'Creative Commons Attribution (CC BY)', 'credit_name' => 'Kanal Contoh',
            'source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'position' => 100, 'is_free_preview' => true, 'is_approved' => false]);

        $this->get("/belajar/{$course->slug}/{$l->id}")->assertNotFound();

        $l->update(['is_approved' => true]);
        $this->get("/belajar/{$course->slug}/{$l->id}")->assertOk()
            ->assertSee('data-id="dQw4w9WgXcQ"', false)->assertSee('i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', false)->assertSee('Kanal Contoh')->assertSee('Creative Commons Attribution');
    }

    public function test_skor_kesiapan_di_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/karier/data-analyst/target')->assertRedirect();
        $this->get('/dashboard')->assertOk()->assertSee('Skor kesiapan karier')->assertSee('Data Analyst');

        $this->post('/simulasi/karier-data-analyst/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();
        foreach ($attempt->answers as $a) {
            $this->postJson("/simulasi/ujian/{$attempt->id}/jawab", ['question_id' => $a->question_id, 'selected' => $a->question->answer])->assertOk();
        }
        $this->post("/simulasi/ujian/{$attempt->id}/selesai");

        $score = app(\App\Services\Careers\ReadinessScore::class)->for($user, Career::where('slug', 'data-analyst')->first());
        $this->assertSame(100, $score['parts']['exam']['value']);
        $this->assertSame(50, $score['total']); // simulasi 100% x bobot 50
    }

    public function test_admin_mengelola_karier_dinamis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs(User::factory()->create())->get('/admin/karier')->assertForbidden();

        $this->actingAs($admin)->get('/admin/karier')->assertOk()->assertSee('Data Analyst');
        $this->post('/admin/karier', ['name' => 'Product Manager', 'division' => 'Produk', 'summary' => 'Menentukan produk apa yang dibangun.'])->assertRedirect();
        $c = Career::where('slug', 'product-manager')->firstOrFail();
        $this->assertSame(3, $c->examType->segments()->count());
        $this->assertSame(16, Career::count());

        $this->put("/admin/karier/{$c->id}", ['name' => 'Product Manager', 'division' => 'Produk', 'summary' => 'x', 'rank' => 99, 'tags' => 'bisnis, analitis'])->assertRedirect();
        $this->assertFalse($c->fresh()->is_active); // checkbox tidak dikirim = nonaktif
        $this->get('/simulasi?q=product')->assertDontSee('Product Manager');

        $this->get('/admin/belajar')->assertOk();
    }
}
