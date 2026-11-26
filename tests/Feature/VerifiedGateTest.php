<?php

namespace Tests\Feature;

use App\Models\Entitlement;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedGateTest extends TestCase
{
    use RefreshDatabase;

    private function premium(User $u): User
    {
        Entitlement::create(['user_id' => $u->id, 'type' => 'subscription', 'expires_at' => now()->addMonth()]);

        return $u;
    }

    private function plan(): Plan
    {
        $this->seed(PlanSeeder::class);

        return Plan::where('is_active', true)->firstOrFail();
    }

    public function test_pengguna_belum_terverifikasi_ditolak_dari_fitur_premium_walau_sudah_premium(): void
    {
        $u = $this->premium(User::factory()->unverified()->create());

        foreach (['/dashboard/rencana', '/dashboard/wawancara'] as $url) {
            $this->actingAs($u)->get($url)->assertRedirect(route('verification.notice'))->assertSessionHas('error');
        }
        $this->actingAs($u)->postJson('/dashboard/wawancara', ['role' => 'Analis', 'kind' => 'hr'])->assertForbidden();
    }

    public function test_pengguna_terverifikasi_dan_premium_tetap_lolos(): void
    {
        $u = $this->premium(User::factory()->create());

        $this->actingAs($u)->get('/dashboard/rencana')->assertOk()->assertDontSee('khusus Premium');
    }

    public function test_belum_terverifikasi_tidak_bisa_membeli_langganan(): void
    {
        $plan = $this->plan();
        $u = User::factory()->unverified()->create();

        $this->actingAs($u)->post('/harga/langganan', ['plan_id' => $plan->id])
            ->assertRedirect(route('verification.notice'))->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    public function test_belum_terverifikasi_tetap_bisa_membeli_tiket_simulasi(): void
    {
        // Tiket hanya membuka simulasi di akunnya sendiri, jadi tidak butuh email terverifikasi (beda dengan Premium).
        $this->seed([PlanSeeder::class, \Database\Seeders\ExamSeeder::class]);
        $u = User::factory()->unverified()->create();
        $type = ExamType::selection()->first();
        $this->assertNotNull($type, 'Tipe ujian untuk tiket harus ada setelah seeding.');

        $this->actingAs($u)->post('/harga/beli', ['exam_type_id' => $type->id])->assertRedirect();
        $this->assertSame(1, Order::where('user_id', $u->id)->count());
    }

    public function test_terverifikasi_bisa_membeli_langganan(): void
    {
        $plan = $this->plan();
        $u = User::factory()->create();

        $this->actingAs($u)->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect();

        $this->assertSame(1, Order::where('user_id', $u->id)->count());
    }

    public function test_setelah_verifikasi_pembelian_langsung_terbuka(): void
    {
        $plan = $this->plan();
        $u = User::factory()->unverified()->create();

        $this->actingAs($u)->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect(route('verification.notice'));

        $u->markEmailAsVerified();

        $this->actingAs($u->fresh())->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect();
        $this->assertSame(1, Order::count());
    }

    public function test_tamu_boleh_membeli_premium_cukup_dengan_email(): void
    {
        $plan = $this->plan();

        $this->post('/harga/langganan', ['plan_id' => $plan->id, 'email' => 'budi@example.com'])->assertRedirect();

        $this->assertSame(1, Order::count());
        $this->assertSame('budi@example.com', Order::firstOrFail()->meta['email']);
    }

    public function test_halaman_harga_untuk_tamu_meminta_email_bukan_login(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get('/harga')->assertOk()->assertSee('Email untuk akses Premium')->assertDontSee('Masuk untuk membayar');
    }

    public function test_akun_hasil_pembelian_tidak_diblokir_verifikasi_email_tapi_pendaftar_biasa_tetap(): void
    {
        $dibeli = User::factory()->unverified()->create();
        $dibeli->forceFill(['auto_password' => true])->save();
        $biasa = User::factory()->unverified()->create();

        $this->assertFalse(\App\Http\Middleware\RequireVerifiedEmail::blocks($dibeli->fresh()));
        $this->assertTrue(\App\Http\Middleware\RequireVerifiedEmail::blocks($biasa));
    }

    public function test_pemberitahuan_tanpa_pengembalian_dana_tampil_di_harga_dan_tagihan(): void
    {
        $plan = $this->plan();
        $u = User::factory()->create();

        $this->actingAs($u)->get('/harga')->assertOk()->assertSee('Tanpa pengembalian dana');

        $this->actingAs($u)->post('/harga/langganan', ['plan_id' => $plan->id]);
        $order = Order::firstOrFail();
        $this->actingAs($u)->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee('Tanpa pengembalian dana');
    }

    public function test_staf_tidak_terhalang_verifikasi(): void
    {
        $staff = User::factory()->unverified()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/dashboard/rencana')->assertOk();
    }
}
