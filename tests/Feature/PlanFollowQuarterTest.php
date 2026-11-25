<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Premium 6 bulan ikut harga 3 bulan (kolom admin) dan baris Wawancara AI di tabel harga. */
class PlanFollowQuarterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_paket_6_bulan_otomatis_dua_kali_harga_3_bulan(): void
    {
        $q = Plan::where('code', 'premium-3m')->first();
        $h = Plan::where('code', 'premium-6m')->first();
        $this->assertTrue($h->follow_quarter);
        $this->assertSame($q->price * 2, $h->price);

        $admin = User::factory()->create(['role' => 'admin']);
        $rows = Plan::orderBy('id')->get()->mapWithKeys(fn ($p) => [$p->id => ['price' => $p->price, 'is_active' => '1'] + ($p->follow_quarter ? ['follow_quarter' => '1'] : [])])->all();
        $rows[$q->id]['price'] = 75000;
        $rows[$q->id] += ['discount_type' => 'percent', 'discount_value' => 20, 'discount_label' => 'Promo Natal'];

        $this->actingAs($admin)->put(route('admin.plans.prices'), ['plans' => $rows])->assertRedirect()->assertSessionHasNoErrors();

        $h->refresh();
        $this->assertSame(150000, $h->price);
        $this->assertSame('percent', $h->discount_type);
        $this->assertSame(20, $h->discount_value);
        $this->assertSame('Promo Natal', $h->discount_label);

        // Dimatikan: harga 6 bulan bisa diisi sendiri.
        unset($rows[$h->id]['follow_quarter']);
        $rows[$h->id]['price'] = 140000;
        $this->put(route('admin.plans.prices'), ['plans' => $rows])->assertRedirect();
        $this->assertSame(140000, $h->fresh()->price);
        $this->assertFalse($h->fresh()->follow_quarter);
    }

    public function test_halaman_harga_punya_baris_wawancara_ai_dan_opsi_6_bulan(): void
    {
        $this->get('/harga')->assertOk()->assertSee('Wawancara AI')->assertSee('6 bulan');
    }

    public function test_admin_melihat_kolom_ikuti_harga_3_bulan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.settings'))->assertOk()->assertSee('Ikuti harga 3 bulan');
    }
}
