<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use App\Models\ChatUsage;
use App\Models\Entitlement;
use App\Models\User;
use App\Services\Ai\AiQuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kunci jatah harian (<x-quota-lock>) di Asisten AI, Wawancara AI, dan Analisis CV. */
class QuotaLockTest extends TestCase
{
    use RefreshDatabase;

    private const LOCKED = 'class="ql is-locked"';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.groq.key' => 'k', 'services.groq.model' => 'm', 'services.groq.base_url' => 'https://llm.test/v1', 'services.llm.order' => 'groq',
            'platform.chatbot.free_daily' => 2, 'platform.chatbot.premium_daily' => 4,
            'platform.ai_tools.interview.free_daily' => 1,
            'platform.ai_tools.cv_analysis.premium_daily' => 2,
            'platform.premium_only.interview' => false,
        ]);
    }

    private function premium(User $u): User
    {
        Entitlement::create(['user_id' => $u->id, 'type' => 'subscription', 'expires_at' => now()->addMonth()]);

        return $u;
    }

    private function use(User $u, string $feature, int $n): void
    {
        AiUsage::create(['user_id' => $u->id, 'feature' => $feature, 'day' => now()->toDateString(), 'used' => $n]);
    }

    public function test_waktu_isi_ulang_adalah_pergantian_hari_berikutnya(): void
    {
        $this->assertEquals(now()->addDay()->startOfDay(), AiQuota::resetsAt());
        $this->assertTrue(AiQuota::resetsAt()->isFuture());
    }

    public function test_chat_terkunci_saat_jatah_habis_dan_popup_lama_hilang(): void
    {
        $u = User::factory()->create();
        ChatUsage::create(['user_id' => $u->id, 'day' => now()->toDateString(), 'count' => 2]);

        $this->actingAs($u)->get(route('account.chat'))->assertOk()
            ->assertSee(self::LOCKED, false)
            ->assertSee('Jatah pesan hari ini habis')
            ->assertSee('data-reset=', false)
            ->assertSee('Lihat Premium')
            ->assertDontSee('data-chat-limit', false);
    }

    public function test_chat_tidak_terkunci_selama_jatah_masih_ada(): void
    {
        $u = User::factory()->create();
        ChatUsage::create(['user_id' => $u->id, 'day' => now()->toDateString(), 'count' => 1]);

        $this->actingAs($u)->get(route('account.chat'))->assertOk()->assertDontSee(self::LOCKED, false);
    }

    public function test_kunci_chat_untuk_premium_tanpa_ajakan_upgrade(): void
    {
        $u = $this->premium(User::factory()->create());
        ChatUsage::create(['user_id' => $u->id, 'day' => now()->toDateString(), 'count' => 4]);

        $this->actingAs($u)->get(route('account.chat'))->assertOk()
            ->assertSee(self::LOCKED, false)
            ->assertSee('paket Premium hari ini')
            ->assertDontSee('Lihat Premium');
    }

    public function test_pengiriman_saat_habis_ditolak_server_dengan_waktu_isi_ulang(): void
    {
        $u = User::factory()->create();
        ChatUsage::create(['user_id' => $u->id, 'day' => now()->toDateString(), 'count' => 2]);

        $this->actingAs($u)->postJson(route('account.chat.send'), ['message' => 'Halo'])
            ->assertStatus(429)
            ->assertJson(['remaining' => 0, 'limit' => 2, 'reset_at' => AiQuota::resetsAt()->toIso8601String()]);
    }

    public function test_wawancara_terkunci_saat_jatah_habis_tetapi_riwayat_tetap_tampil(): void
    {
        $u = User::factory()->create();
        $this->use($u, 'interview', 1);

        $this->actingAs($u)->get(route('account.interview'))->assertOk()
            ->assertSee(self::LOCKED, false)
            ->assertSee('Jatah wawancara hari ini habis')
            ->assertSee('Riwayat dan hasil wawancara sebelumnya tetap bisa dibuka.');
    }

    public function test_wawancara_terbuka_bila_jatah_ada(): void
    {
        $this->actingAs(User::factory()->create())->get(route('account.interview'))->assertOk()
            ->assertDontSee(self::LOCKED, false);
    }

    public function test_wawancara_tidak_dikunci_bila_penilai_ai_belum_aktif(): void
    {
        config(['services.groq.key' => '']);
        $u = User::factory()->create();
        $this->use($u, 'interview', 1);

        $this->actingAs($u)->get(route('account.interview'))->assertOk()->assertDontSee(self::LOCKED, false);
    }

    public function test_analisis_cv_terkunci_saat_jatah_habis(): void
    {
        $pro = $this->premium(User::factory()->create());
        $this->use($pro, 'cv_analysis', 2);

        $this->actingAs($pro)->get(route('cv.index'))->assertOk()
            ->assertSee(self::LOCKED, false)
            ->assertSee('Jatah analisis CV hari ini habis')
            ->assertSee('Sisa jatah analisis hari ini: 0/2');
    }

    public function test_analisis_cv_terbuka_bila_jatah_ada(): void
    {
        $pro = $this->premium(User::factory()->create());
        $this->use($pro, 'cv_analysis', 1);

        $this->actingAs($pro)->get(route('cv.index'))->assertOk()->assertDontSee(self::LOCKED, false);
    }

    public function test_kunci_tidak_dipakai_di_hari_baru(): void
    {
        $u = User::factory()->create();
        AiUsage::create(['user_id' => $u->id, 'feature' => 'interview', 'day' => now()->subDay()->toDateString(), 'used' => 1]);

        $this->actingAs($u)->get(route('account.interview'))->assertOk()->assertDontSee(self::LOCKED, false);
    }
}
