<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Cari arah kariermu": soal situasi, hasil ringkas vs lengkap, dan coba simulasi umum (hasil khusus Premium). */
class CareerDirectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();
        $plan = Plan::where('code', 'premium-1m')->firstOrFail();
        $order = Order::create(['user_id' => $user->id, 'invoice_no' => 'INV-D-'.$user->id, 'item_type' => 'plan', 'item_id' => $plan->id,
            'amount' => $plan->price, 'status' => 'paid', 'gateway' => 'fake', 'paid_at' => now()]);
        app(EntitlementService::class)->grantFor($order);

        return $user;
    }

    private function answers(int $pick = 0): array
    {
        return array_fill(0, count(config('arah-karier.questions')), $pick);
    }

    public function test_halaman_karier_hanya_memuat_tes_karier_dan_menautkan_ke_coba_simulasi(): void
    {
        $html = $this->get('/arah-karier')->assertOk()
            ->assertSee('Cari arah kariermu')->assertSee('data-qz-card', false)
            ->assertSee(route('exam.trial'), false)
            ->assertDontSee('data-tr-form', false)->assertDontSee('data-tr-exam', false)
            ->getContent();

        $this->assertStringNotContainsString('"analitis":2', $html);
    }

    public function test_halaman_coba_simulasi_hanya_soal_dengan_timer_dan_kunci_tidak_bocor(): void
    {
        $total = count(config('arah-karier.trial'));
        $html = $this->get('/simulasi/coba')->assertOk()
            ->assertSee('Coba simulasi')->assertSee("{$total} soal kasus kerja dalam")
            ->assertSee('data-tx-exam', false)->assertSee('data-tx-time', false)
            ->assertDontSee('data-qz-card', false)->assertDontSee('data-qz-stage', false)
            ->getContent();

        $this->assertStringNotContainsString('"answer"', $html);
        $this->assertStringNotContainsString('Tiap suku dikali 3', $html);
        $this->assertStringContainsString('SECONDS = 300', $html);
    }

    public function test_coba_simulasi_beberapa_soal_dan_durasi_lima_menit(): void
    {
        $this->assertSame(5, config('arah-karier.trial_minutes'));
        $this->assertGreaterThanOrEqual(10, count(config('arah-karier.trial')));
    }

    public function test_bank_soal_valid(): void
    {
        $dims = array_keys(config('arah-karier.dimensions'));
        foreach (config('arah-karier.questions') as $q) {
            $this->assertCount(4, $q['options']);
            foreach ($q['options'] as $o) {
                $this->assertNotEmpty(array_intersect(array_keys($o[1]), $dims));
                $this->assertSame([], array_diff(array_keys($o[1]), $dims));
            }
        }
        foreach (config('arah-karier.trial') as $q) {
            $this->assertArrayHasKey($q['answer'], $q['options']);
        }
        foreach (config('arah-karier.profiles') as $slug => $p) {
            $this->assertSame([], array_diff(array_keys($p), $dims), $slug);
        }
    }

    public function test_hasil_lengkap_untuk_semua_pengguna_tanpa_kunci_premium(): void
    {
        foreach ([null, User::factory()->create()] as $user) {
            $r = ($user ? $this->actingAs($user) : $this)->postJson('/arah-karier/hasil', ['answers' => $this->answers(0)])->assertOk()
                ->assertJsonStructure(['top' => ['name', 'pct', 'summary', 'url'], 'strong', 'careers' => [['name', 'pct', 'reasons', 'evidence', 'gaps', 'steps']], 'profile', 'contrast', 'insight']);

            $this->assertArrayNotHasKey('locked', $r->json());
            $this->assertArrayNotHasKey('premium', $r->json());
            $this->assertCount(3, $r->json('careers'));
            $this->assertCount(7, $r->json('profile'));
            $this->assertGreaterThanOrEqual($r->json('careers.1.pct'), $r->json('careers.0.pct'));
        }
    }

    public function test_jawaban_berbeda_menghasilkan_arah_berbeda(): void
    {
        $a = $this->postJson('/arah-karier/hasil', ['answers' => $this->answers(0)])->json('top.slug');
        $b = $this->postJson('/arah-karier/hasil', ['answers' => $this->answers(2)])->json('top.slug');
        $this->assertNotSame($a, $b);
    }

    public function test_validasi_jawaban_tes(): void
    {
        $this->postJson('/arah-karier/hasil', ['answers' => [0, 1]])->assertStatus(422);
        $this->postJson('/arah-karier/hasil', ['answers' => $this->answers(9)])->assertStatus(422);
        $this->postJson('/arah-karier/hasil', [])->assertStatus(422);
    }

    public function test_coba_simulasi_skor_dan_pembahasan_terbuka_untuk_semua(): void
    {
        $answers = collect(config('arah-karier.trial'))->pluck('answer')->all();
        $answers[0] = ($answers[0] + 1) % 4; // satu salah

        foreach ([null, User::factory()->create()] as $user) {
            $res = ($user ? $this->actingAs($user) : $this)->postJson('/simulasi/coba', ['answers' => $answers])->assertOk()
                ->assertJsonPath('correct', count($answers) - 1)->assertJsonPath('total', count($answers))
                ->assertJsonPath('items.0.ok', false)->assertJsonStructure(['items' => [['text', 'right', 'explain']], 'areas']);
            $this->assertArrayNotHasKey('premium', $res->json());
        }

        // Waktu habis: jawaban boleh ada yang kosong dan dihitung salah.
        $this->postJson('/simulasi/coba', ['answers' => array_replace($answers, [1 => null, 2 => null])])->assertOk()
            ->assertJsonPath('items.1.picked', null);
    }

    public function test_landing_mengajak_cari_arah_karier_dan_coba_simulasi(): void
    {
        $this->get('/')->assertOk()->assertSee('Cari arah kariermu')->assertSee(route('careers.quiz'), false)
            ->assertSee(route('exam.trial'), false)->assertSee('Coba simulasi 5 menit')->assertSee('dr-career', false)->assertSee('dr-sim', false)->assertDontSee(url('/karier').'"', false);
    }
}
