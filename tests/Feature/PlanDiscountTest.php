<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Diskon paket Premium: aturan harga, tagihan, halaman Harga, spanduk situs, dan form admin. */
class PlanDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        // Gateway palsu: tidak ada panggilan jaringan, tagihan langsung dibuat.
        config(['platform.payment.driver' => 'fake']);
        $this->app->forgetInstance(\App\Contracts\PaymentGateway::class);
    }

    private function plan(array $discount = []): Plan
    {
        $plan = Plan::where('code', 'premium-3m')->firstOrFail();
        $plan->update($discount);

        return $plan->fresh();
    }

    public function test_harga_akhir_mengikuti_jenis_dan_rentang_waktu_diskon(): void
    {
        $plan = $this->plan(['discount_type' => 'percent', 'discount_value' => 30, 'discount_starts_at' => now()->subDay(), 'discount_ends_at' => now()->addDay()]);
        $this->assertTrue($plan->isDiscounted());
        $this->assertSame(69000 - 20700, $plan->finalPrice());
        $this->assertSame(30, $plan->discountPercent());

        $plan = $this->plan(['discount_type' => 'amount', 'discount_value' => 9000, 'discount_starts_at' => null, 'discount_ends_at' => null]);
        $this->assertSame(60000, $plan->finalPrice());

        // Belum mulai: harga normal, tetapi tercatat terjadwal.
        $plan = $this->plan(['discount_starts_at' => now()->addDay(), 'discount_ends_at' => now()->addDays(3)]);
        $this->assertFalse($plan->isDiscounted());
        $this->assertTrue($plan->isDiscountScheduled());
        $this->assertSame(69000, $plan->finalPrice());

        // Sudah berakhir.
        $plan = $this->plan(['discount_starts_at' => now()->subDays(3), 'discount_ends_at' => now()->subDay()]);
        $this->assertFalse($plan->isDiscounted());
        $this->assertSame(69000, $plan->finalPrice());
    }

    public function test_potongan_tidak_membuat_harga_di_bawah_batas_bayar(): void
    {
        $plan = $this->plan(['discount_type' => 'amount', 'discount_value' => 500000, 'discount_starts_at' => null, 'discount_ends_at' => null]);

        $this->assertSame(Plan::MIN_PAYABLE, $plan->finalPrice());
    }

    public function test_tagihan_memakai_harga_diskon_dan_menyimpan_rinciannya(): void
    {
        $plan = $this->plan(['discount_type' => 'percent', 'discount_value' => 20, 'discount_label' => 'Promo Akhir Tahun', 'discount_starts_at' => null, 'discount_ends_at' => now()->addDay()]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect();
        $order = Order::latest('id')->firstOrFail();

        $this->assertSame(69000 - 13800, (int) $order->amount);
        $this->assertSame(69000, $order->meta['original_amount']);
        $this->assertSame(13800, $order->meta['discount']);
        $this->assertSame('Promo Akhir Tahun', $order->meta['promo_label']);

        $this->get("/harga/tagihan/{$order->invoice_no}")->assertOk()->assertSee('Promo Akhir Tahun')->assertSee('Rp 55.200');

        // Pesan lagi dengan harga sama memakai tagihan yang sama.
        $this->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect("/harga/tagihan/{$order->invoice_no}");
        $this->assertSame(1, Order::count());

        // Diskon berubah: dibuat tagihan baru dengan harga baru.
        $plan->update(['discount_value' => 50]);
        $this->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect();
        $this->assertSame(2, Order::count());
        $this->assertSame(69000 - 34500, (int) Order::latest('id')->first()->amount);
    }

    public function test_tanpa_diskon_tagihan_memakai_harga_normal(): void
    {
        $plan = $this->plan();
        $this->actingAs(User::factory()->create())->post('/harga/langganan', ['plan_id' => $plan->id])->assertRedirect();

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame(69000, (int) $order->amount);
        $this->assertArrayNotHasKey('original_amount', $order->meta);
    }

    public function test_halaman_harga_dan_spanduk_situs_menampilkan_promo_dengan_tautan_ke_pembayaran(): void
    {
        $this->plan(['discount_type' => 'percent', 'discount_value' => 25, 'discount_label' => 'Promo Akhir Tahun', 'discount_starts_at' => now()->subHour(), 'discount_ends_at' => now()->addDays(5)]);

        $this->get('/harga')->assertOk()->assertSee('ck-promo', false)->assertSee('Promo Akhir Tahun')->assertSee('&quot;percent&quot;:25', false)->assertSee('data-promo-countdown', false);

        // Spanduk muncul di halaman lain dan mengarah ke bagian paket di halaman Harga.
        $this->get('/')->assertOk()->assertSee('pb-bar', false)->assertSee(route('billing.index').'#paket', false)->assertSee('Promo Akhir Tahun');

        // Pengguna Premium tidak dibanjiri promo.
        $user = User::factory()->create();
        app(\App\Services\Billing\EntitlementService::class)->grantPremium($user->id, 30);
        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('pb-bar', false);
    }

    public function test_tanpa_diskon_berjalan_tidak_ada_spanduk(): void
    {
        $this->plan(['discount_type' => 'percent', 'discount_value' => 25, 'discount_starts_at' => now()->addDay(), 'discount_ends_at' => now()->addDays(5)]);

        $this->get('/')->assertOk()->assertDontSee('pb-bar', false);
        $this->get('/harga')->assertOk()->assertDontSee('ck-promo', false);
    }

    public function test_admin_mengatur_diskon_dengan_waktu_wib_dan_menghapusnya(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = $this->plan();
        $base = ['name' => $plan->name, 'price' => $plan->price, 'interval' => $plan->interval, 'features' => 'Satu', 'is_active' => '1'];

        $this->actingAs($admin)->get(route('admin.plans.edit', $plan))->assertOk()->assertSee('data-disc-form', false)->assertSee('datetime-local', false);

        $this->put(route('admin.plans.update', $plan), $base + [
            'discount_type' => 'percent', 'discount_value' => 30, 'discount_label' => 'Promo Gajian',
            'discount_starts_at' => '2026-12-01T08:00', 'discount_ends_at' => '2026-12-05T23:59',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $plan->refresh();
        $this->assertSame('percent', $plan->discount_type);
        $this->assertSame(30, $plan->discount_value);
        $this->assertSame('Promo Gajian', $plan->discount_label);
        // Diisi dalam WIB (UTC+7), disimpan UTC.
        $this->assertSame('2026-12-01 01:00:00', $plan->discount_starts_at->toDateTimeString());
        $this->assertSame('2026-12-05 16:59:00', $plan->discount_ends_at->toDateTimeString());

        $this->put(route('admin.plans.update', $plan), $base + ['discount_type' => '', 'discount_value' => ''])->assertRedirect();
        $plan->refresh();
        $this->assertNull($plan->discount_type);
        $this->assertNull($plan->discount_starts_at);
        $this->assertNull($plan->discount_ends_at);
    }

    public function test_admin_ditolak_untuk_diskon_tidak_masuk_akal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = $this->plan();
        $base = ['name' => $plan->name, 'price' => $plan->price, 'interval' => $plan->interval, 'features' => 'Satu', 'is_active' => '1'];
        $this->actingAs($admin);

        $this->put(route('admin.plans.update', $plan), $base + ['discount_type' => 'percent', 'discount_value' => 95])->assertSessionHasErrors('discount_value');
        $this->put(route('admin.plans.update', $plan), $base + ['discount_type' => 'amount', 'discount_value' => 69000])->assertSessionHasErrors('discount_value');
        $this->put(route('admin.plans.update', $plan), $base + ['discount_type' => 'percent'])->assertSessionHasErrors('discount_value');
        $this->put(route('admin.plans.update', $plan), $base + ['discount_value' => 10])->assertSessionHasErrors('discount_type');
        $this->put(route('admin.plans.update', $plan), $base + [
            'discount_type' => 'percent', 'discount_value' => 10,
            'discount_starts_at' => '2026-12-05T10:00', 'discount_ends_at' => '2026-12-01T10:00',
        ])->assertSessionHasErrors('discount_ends_at');

        $this->assertNull($plan->fresh()->discount_type);
    }

    public function test_pengumuman_dari_admin_tampil_di_situs_publik_dan_dashboard(): void
    {
        app(\App\Services\Settings\SiteSettings::class)->put('announcement', ['enabled' => true, 'text' => 'Simulasi CPNS 2026 diperbarui', 'tone' => 'success', 'url' => null, 'url_label' => null]);

        $this->get('/')->assertOk()->assertSee('Simulasi CPNS 2026 diperbarui')->assertSee('sx-banner', false);
        $this->get('/harga')->assertOk()->assertSee('Simulasi CPNS 2026 diperbarui');
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Simulasi CPNS 2026 diperbarui');
    }

    public function test_diskon_bisa_diatur_langsung_dari_pengaturan_pembayaran(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plans = Plan::orderBy('id')->get();
        $rows = $plans->mapWithKeys(fn ($p) => [$p->id => ['price' => $p->price, 'is_active' => '1']])->all();
        $first = $plans->first();

        $this->actingAs($admin)->get(route('admin.settings'))->assertOk()->assertSee('Atur harga dan diskon')->assertSee('datetime-local', false);

        $rows[$first->id] += ['discount_type' => 'amount', 'discount_value' => 5000, 'discount_label' => 'Promo Cepat', 'discount_starts_at' => '', 'discount_ends_at' => '2030-01-01T12:00'];
        $this->put(route('admin.plans.prices'), ['plans' => $rows])->assertRedirect()->assertSessionHasNoErrors();

        $first->refresh();
        $this->assertSame('amount', $first->discount_type);
        $this->assertSame(5000, $first->discount_value);
        $this->assertSame('2030-01-01 05:00:00', $first->discount_ends_at->toDateTimeString());
        $this->assertTrue($first->isDiscounted());
        $this->get(route('admin.settings'))->assertOk()->assertSee('Sedang berjalan');

        // Diskon salah pada satu paket: tidak ada yang tersimpan.
        $rows[$first->id]['discount_value'] = 999999999;
        $this->put(route('admin.plans.prices'), ['plans' => $rows])->assertSessionHasErrors("plans.{$first->id}.discount_value");
        $this->assertSame(5000, $first->fresh()->discount_value);
    }
}
