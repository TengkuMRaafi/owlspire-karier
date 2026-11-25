<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Exam\QuestionImporter;
use Database\Seeders\ExamSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExamResultVisualTest extends TestCase
{
    use RefreshDatabase;

    private function fullAttempt(): array
    {
        $this->seed(ExamSeeder::class);
        // Bank awal hanya 34 soal TKP; muat bank CPNS agar tiap bagian punya >= 34 soal untuk ujian penuh.
        // Muat bank CPNS saja, bukan seluruh bank, supaya tes tetap cepat.
        $importer = app(QuestionImporter::class);
        $importer->import($importer->parseJson((string) file_get_contents(database_path('seeders/data/bank-soal/cpns.json'))));
        $user = User::factory()->create();
        $type = ExamType::where('code', 'cpns')->firstOrFail();
        app(EntitlementService::class)->grantExamPass($user->id, $type->id, 1);
        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'full']);
        $attempt = ExamAttempt::firstOrFail();
        $this->post("/simulasi/ujian/{$attempt->id}/selesai");

        return [$user, $attempt->refresh()];
    }

    public function test_hasil_penuh_menampilkan_passing_grade_nyata_dan_peringkat_terkunci(): void
    {
        config(['services.groq.key' => null]);
        [, $attempt] = $this->fullAttempt();

        $this->get("/simulasi/hasil/{$attempt->id}")
            ->assertOk()
            ->assertSee('Passing grade')
            ->assertSee('Belum lolos')          // jawaban kosong tidak mungkin lolos
            ->assertSee('Analisis lanjutan')    // grafik tambahan hanya di ujian penuh
            ->assertSee('Gambaran cepat')
            // Ujian penuh = 100 soal (34/33/33) x 5 poin -> maks 170/165/165; ambang = persen resmi x maks.
            ->assertSee('ambang 74', false)   // TWK 43,33% x 170
            ->assertSee('ambang 75', false)   // TIU 45,71% x 165
            ->assertSee('ambang 122', false)  // TKP 73,77% x 165
            ->assertSee('Kepmenpan RB No. 321 Tahun 2024')
            ->assertSee(route('billing.index'), false)  // tombol Peringkat kamu menuju Premium
            ->assertSee('Tanya Owlspire tentang hasilmu');          // blok selalu ada, isinya fallback tanpa Groq
    }

    public function test_tanpa_groq_ringkasan_tetap_terisi_dari_aturan(): void
    {
        config(['services.groq.key' => null]);
        [, $attempt] = $this->fullAttempt();

        $this->postJson("/simulasi/hasil/{$attempt->id}/ai/ringkasan")
            ->assertOk()
            ->assertJson(['ai' => false])
            ->assertJsonStructure(['text']);
    }

    public function test_premium_melihat_peringkat_asli_bukan_tombol_kunci(): void
    {
        config(['services.groq.key' => null]);
        [$user, $attempt] = $this->fullAttempt();
        $this->seed(PlanSeeder::class);
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-R-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);

        $this->get("/simulasi/hasil/{$attempt->id}")->assertOk()->assertSee('dari 1 peserta')->assertDontSee('Buka dengan Premium');
    }

    public function test_ringkasan_ai_memakai_groq_dan_di_cache(): void
    {
        config(['services.groq.key' => 'test-key', 'services.groq.model' => 'openai/gpt-oss-120b']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Kamu hampir lolos.']]]])]);
        [, $attempt] = $this->fullAttempt();

        $this->get("/simulasi/hasil/{$attempt->id}")->assertSee('Tanya Owlspire tentang hasilmu');
        $this->postJson("/simulasi/hasil/{$attempt->id}/ai/ringkasan")->assertOk()->assertJson(['text' => 'Kamu hampir lolos.', 'ai' => true]);
        $this->postJson("/simulasi/hasil/{$attempt->id}/ai/ringkasan")->assertOk();

        Http::assertSentCount(1); // panggilan kedua dari cache
    }

    public function test_ai_gagal_memakai_ringkasan_fallback_bukan_error(): void
    {
        config(['services.groq.key' => 'test-key']);
        Http::fake(['api.groq.com/*' => Http::response([], 500)]);
        [, $attempt] = $this->fullAttempt();

        $this->postJson("/simulasi/hasil/{$attempt->id}/ai/ringkasan")->assertOk()->assertJson(['ai' => false]);
    }

    public function test_orang_lain_tidak_bisa_memanggil_ai_untuk_percobaan_ini(): void
    {
        config(['services.groq.key' => 'test-key']);
        [, $attempt] = $this->fullAttempt();

        $this->actingAs(User::factory()->create())->postJson("/simulasi/hasil/{$attempt->id}/ai/ringkasan")->assertNotFound();
    }

    public function test_mode_coba_ringkas_punya_grafik_ambang_ringkasan_dan_peringkat_terkunci(): void
    {
        config(['services.groq.key' => null]);
        $this->seed(ExamSeeder::class);
        $this->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();
        $this->post("/simulasi/ujian/{$attempt->id}/selesai");

        $this->get("/simulasi/hasil/{$attempt->id}")
            ->assertOk()
            ->assertSee('Perkiraan passing grade')
            ->assertSee('Di bawah ambang')
            ->assertSee('Gambaran cepat')
            ->assertSee('Tanya Owlspire tentang hasilmu')
            ->assertSee('Peringkat kamu')                  // peringkat juga ada di mode coba (terkunci untuk non-Premium)
            ->assertSee(route('billing.index'), false)
            ->assertDontSee('Analisis lanjutan');
        $this->postJson("/simulasi/hasil/{$attempt->id}/ai/ringkasan")->assertOk()->assertJson(['ai' => false]);
    }

    public function test_premium_melihat_peringkat_asli_di_mode_coba(): void
    {
        config(['services.groq.key' => null]);
        $this->seed(ExamSeeder::class);
        $user = User::factory()->create();
        $this->seed(PlanSeeder::class);
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-T-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);
        $this->actingAs($user)->post('/simulasi/cpns/mulai', ['mode' => 'trial']);
        $attempt = ExamAttempt::firstOrFail();
        $this->post("/simulasi/ujian/{$attempt->id}/selesai");

        $this->get("/simulasi/hasil/{$attempt->id}")->assertOk()->assertSee('dari 1 peserta')->assertDontSee('Buka dengan Premium');
    }
}
