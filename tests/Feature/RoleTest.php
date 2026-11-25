<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Billing\EntitlementService;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Peran akun: admin (terkunci, akses penuh), staf (di bawah admin), dan pengguna Gratis/Premium. */
class RoleTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    // ---------------------------------------------------------------- akses panel

    public function test_staf_membuka_area_konten_tapi_tidak_uang_dan_pengguna(): void
    {
        $this->seed(ExamSeeder::class);
        $this->actingAs($this->user('staff'));

        foreach (['/admin', '/admin/soal', '/admin/soal/impor', '/admin/hasil-ujian', '/admin/pengaturan'] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (['/admin/karier', '/admin/belajar'] as $path) {
            $this->assertNotSame(403, $this->get($path)->status(), $path);
        }
        foreach (['/admin/jenis-ujian', '/admin/pesanan', '/admin/pengguna'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_staf_tidak_bisa_mengubah_role_atau_membuat_akun(): void
    {
        $staff = $this->user('staff');
        $target = $this->user();

        $this->actingAs($staff)->put(route('admin.users.role', $target), ['role' => 'staff'])->assertForbidden();
        $this->post(route('admin.users.store'), ['name' => 'Baru', 'email' => 'baru@example.com', 'password' => 'rahasia-123'])->assertForbidden();
        $this->assertSame('member', $target->fresh()->role);
    }

    public function test_menu_dan_dashboard_staf_tanpa_uang(): void
    {
        $this->seed(ExamSeeder::class);

        $this->actingAs($this->user('staff'))->get('/admin')->assertOk()
            ->assertSee('Bank soal')
            ->assertDontSee('Pendapatan')
            ->assertDontSee('href="'.route('admin.orders.index').'"', false)
            ->assertDontSee('href="'.route('admin.users.index').'"', false);

        $this->actingAs($this->user('admin'))->get('/admin')->assertOk()
            ->assertSee('Pendapatan')
            ->assertSee('href="'.route('admin.orders.index').'"', false);
    }

    public function test_pengaturan_admin_memisahkan_tab_admin_dan_staf(): void
    {
        $this->actingAs($this->user('admin'))->get('/admin/pengaturan')->assertOk()
            ->assertSee('Peran dan akses')->assertSee('Pembayaran')->assertSee('Info sistem');

        $this->actingAs($this->user('staff'))->get('/admin/pengaturan')->assertOk()
            ->assertSee('Ganti kata sandi')
            ->assertDontSee('Peran dan akses')->assertDontSee('Info sistem');
    }

    // ---------------------------------------------------------------- admin terkunci

    public function test_role_admin_tidak_bisa_diubah_lewat_panel(): void
    {
        $admin = $this->user('admin');
        $other = $this->user('admin');

        $this->actingAs($admin)->put(route('admin.users.role', $other), ['role' => 'member'])->assertSessionHas('error');
        $this->assertSame('admin', $other->fresh()->role);
    }

    public function test_admin_tidak_bisa_diberikan_lewat_panel(): void
    {
        $admin = $this->user('admin');
        $member = $this->user();

        $this->actingAs($admin)->put(route('admin.users.role', $member), ['role' => 'admin'])->assertSessionHasErrors('role');
        $this->assertSame('member', $member->fresh()->role);
    }

    public function test_pengaman_model_menolak_penurunan_admin(): void
    {
        $admin = $this->user('admin');

        $this->expectException(\LogicException::class);
        $admin->forceFill(['role' => 'member'])->save();
    }

    public function test_member_bisa_dijadikan_staf_dan_dikembalikan(): void
    {
        $admin = $this->user('admin');
        $member = $this->user();

        $this->actingAs($admin)->put(route('admin.users.role', $member), ['role' => 'staff'])->assertSessionHas('status');
        $this->assertSame('staff', $member->fresh()->role);

        $this->put(route('admin.users.role', $member), ['role' => 'member'])->assertSessionHas('status');
        $this->assertSame('member', $member->fresh()->role);
    }

    // ---------------------------------------------------------------- membuat staf

    public function test_admin_membuat_akun_staf(): void
    {
        $this->actingAs($this->user('admin'))
            ->post(route('admin.users.store'), ['name' => 'Staf Baru', 'email' => 'Staf@Example.com', 'password' => 'rahasia-123'])
            ->assertSessionHasNoErrors();

        $staff = User::where('email', 'staf@example.com')->firstOrFail();
        $this->assertSame('staff', $staff->role);
        $this->assertNotNull($staff->email_verified_at);
    }

    public function test_membuat_staf_menolak_email_ganda_dan_sandi_pendek(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'X', 'email' => $admin->email, 'password' => 'rahasia-123'])->assertSessionHasErrors('email');
        $this->post(route('admin.users.store'), ['name' => 'Xy', 'email' => 'x@example.com', 'password' => 'pendek'])->assertSessionHasErrors('password');
    }

    // ---------------------------------------------------------------- tanpa paket

    public function test_admin_dan_staf_selalu_lolos_pemeriksaan_premium_tapi_member_tidak(): void
    {
        $e = app(EntitlementService::class);

        $this->assertTrue($e->isPremium($this->user('admin')->id));
        $this->assertTrue($e->isPremium($this->user('staff')->id));
        $this->assertFalse($e->isPremium($this->user()->id));
    }

    public function test_admin_tidak_memakai_dashboard_pengguna_dan_dialihkan_ke_panel_admin(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('dashboard.index'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('cv.index'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('billing.index'))->assertRedirect(route('admin.dashboard'));
        // Pengaturan akun (ganti sandi) tetap boleh dibuka admin.
        $this->actingAs($admin)->get(route('account.settings'))->assertOk();
    }

    public function test_admin_tidak_bisa_memilih_target_karier(): void
    {
        $admin = $this->user('admin');
        $career = \App\Models\Career::create(['slug' => 'uji-target', 'name' => 'Uji Target', 'division' => 'Data', 'summary' => 'Ringkasan', 'rank' => 1]);
        $this->actingAs($admin)->post(route('careers.target', $career))->assertRedirect(route('admin.dashboard'));
        $this->assertNull($admin->fresh()->target_career_id);
    }

    public function test_halaman_langganan_dialihkan_untuk_admin_dan_staf(): void
    {
        $this->actingAs($this->user('admin'))->get(route('account.subscription'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->user('staff'))->get(route('account.subscription'))->assertRedirect(route('dashboard.index'));
        $this->actingAs($this->user())->get(route('account.subscription'))->assertOk();
    }
}
