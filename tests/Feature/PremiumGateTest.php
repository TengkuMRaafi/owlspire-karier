<?php

namespace Tests\Feature;

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumGateTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        '/dashboard/rencana' => 'Rencana belajar',
        '/dashboard/wawancara' => 'Wawancara AI',
    ];

    public function test_pengguna_gratis_melihat_halaman_terkunci_dengan_tombol_upgrade(): void
    {
        $u = User::factory()->create();

        foreach (self::PAGES as $url => $title) {
            $this->actingAs($u)->get($url)->assertOk()
                ->assertSee('khusus Premium')->assertSee($title, false)->assertSee(route('account.subscription'), false);
        }
    }

    public function test_aksi_ditolak_untuk_pengguna_gratis(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->postJson('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr'])->assertForbidden();
        $this->actingAs($u)->put('/dashboard/rencana', ['daily_goal' => 5])->assertRedirect(route('account.subscription'));
    }

    public function test_pelanggan_premium_dan_staf_lolos(): void
    {
        $premium = User::factory()->create();
        Entitlement::create(['user_id' => $premium->id, 'type' => 'subscription', 'expires_at' => now()->addMonth()]);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (array_keys(self::PAGES) as $url) {
            $this->actingAs($premium)->get($url)->assertOk()->assertDontSee('khusus Premium');
            // Admin hanya mengelola web: fitur pengguna dialihkan ke panel admin.
            $this->actingAs($admin)->get($url)->assertRedirect(route('admin.dashboard'));
        }
    }

    public function test_langganan_yang_sudah_habis_tidak_lolos(): void
    {
        $u = User::factory()->create();
        Entitlement::create(['user_id' => $u->id, 'type' => 'subscription', 'expires_at' => now()->subDay()]);

        $this->actingAs($u)->get('/dashboard/rencana')->assertOk()->assertSee('khusus Premium');
    }

    public function test_kunci_bisa_dimatikan_lewat_config(): void
    {
        config(['platform.premium_only.plan' => false]);

        $this->actingAs(User::factory()->create())->get('/dashboard/rencana')->assertOk()->assertDontSee('khusus Premium');
    }

    public function test_info_seleksi_dan_pelacak_lamaran_tetap_terbuka_untuk_semua(): void
    {
        $u = User::factory()->create();

        $this->get('/info-seleksi')->assertOk();
        $this->actingAs($u)->get('/dashboard/seleksi')->assertOk()->assertDontSee('khusus Premium');
        $this->actingAs($u)->get('/dashboard/lamaran')->assertOk()->assertDontSee('khusus Premium');
    }
}
