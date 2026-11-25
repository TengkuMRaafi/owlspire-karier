<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\EntitlementService;
use App\Services\Settings\SiteSettings;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kelola pengguna lengkap, akses staf, pengaturan sistem, paket, dan log aktivitas. */
class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_menangguhkan_dan_mengaktifkan_akun_dengan_log(): void
    {
        $admin = $this->admin();
        $u = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.suspend', $u), ['reason' => 'spam'])->assertSessionHas('status');
        $this->assertTrue($u->fresh()->isSuspended());
        $this->assertSame('spam', $u->fresh()->suspended_reason);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.suspended', 'subject_id' => $u->id, 'actor_id' => $admin->id]);

        $this->post(route('admin.users.activate', $u))->assertSessionHas('status');
        $this->assertFalse($u->fresh()->isSuspended());

        // Admin dan akun sendiri tidak bisa ditangguhkan.
        $this->post(route('admin.users.suspend', $admin))->assertSessionHas('error');
        $this->assertFalse($admin->fresh()->isSuspended());
    }

    public function test_admin_memberi_memperpanjang_dan_mencabut_premium(): void
    {
        $this->seed(ExamSeeder::class);
        $svc = app(EntitlementService::class);
        $admin = $this->admin();
        $u = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.premium', $u), ['mode' => 'grant', 'duration' => '30'])->assertSessionHas('status');
        $this->assertTrue($svc->isPremium($u->id));
        $first = $svc->premiumUntil($u->id);

        $this->post(route('admin.users.premium', $u), ['mode' => 'grant', 'duration' => '30']);
        $this->assertTrue($svc->premiumUntil($u->id)->greaterThan($first->copy()->addDays(29)));

        $this->post(route('admin.users.premium', $u), ['mode' => 'revoke'])->assertSessionHas('status');
        $this->assertFalse($svc->isPremium($u->id));

        $this->post(route('admin.users.premium', $u), ['mode' => 'grant', 'duration' => 'lifetime']);
        $this->assertTrue($svc->isPremium($u->id));
        $this->assertNull($svc->premiumUntil($u->id));

        // Admin/staf tidak punya paket.
        $staff = User::factory()->create(['role' => 'staff']);
        $this->post(route('admin.users.premium', $staff), ['mode' => 'grant', 'duration' => '30'])->assertSessionHas('error');
    }

    public function test_aksi_keamanan_admin_pada_pengguna(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $admin = $this->admin();
        $u = User::factory()->unverified()->create();

        $this->actingAs($admin)->post(route('admin.users.reset', $u))->assertSessionHas('status');
        \Illuminate\Support\Facades\Notification::assertSentTo($u, \App\Notifications\ResetPasswordId::class);

        $this->post(route('admin.users.force', $u));
        $this->assertTrue($u->fresh()->must_change_password);
        $this->post(route('admin.users.verify', $u));
        $this->assertNotNull($u->fresh()->email_verified_at);
        $this->post(route('admin.users.logout', $u))->assertSessionHas('status');
    }

    public function test_daftar_pengguna_punya_filter_status_dan_aksi_massal(): void
    {
        $admin = $this->admin();
        $a = User::factory()->create(['name' => 'Aktif Satu', 'email' => 'aktif.satu@example.com']);
        $b = User::factory()->create(['name' => 'Tangguh Dua', 'email' => 'tangguh.dua@example.com']);
        $b->forceFill(['suspended_at' => now()])->save();

        $this->actingAs($admin)->get('/admin/pengguna?status=ditangguhkan')->assertOk()->assertSee('tangguh.dua@example.com')->assertDontSee('aktif.satu@example.com');
        $this->get('/admin/pengguna?verifikasi=belum&urut=login&per=50')->assertOk();

        $this->post(route('admin.users.bulk'), ['action' => 'suspend', 'ids' => [$a->id, $admin->id]])->assertSessionHas('status');
        $this->assertTrue($a->fresh()->isSuspended());
        $this->assertFalse($admin->fresh()->isSuspended()); // akun sendiri dilewati
        $this->post(route('admin.users.bulk'), ['action' => 'activate', 'ids' => [$a->id, $b->id]]);
        $this->assertFalse($a->fresh()->isSuspended());
        $this->assertFalse($b->fresh()->isSuspended());
        $this->post(route('admin.users.bulk'), ['action' => 'suspend', 'ids' => []])->assertSessionHasErrors('ids');
    }

    public function test_ekspor_excel_pengguna_aman_dari_formula_dan_hanya_untuk_admin(): void
    {
        User::factory()->create(['name' => '=HYPERLINK("http://x")', 'email' => 'csv@example.com']);

        $this->actingAs($this->admin());
        $res = $this->get('/admin/pengguna/ekspor')->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $res->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $res->headers->get('content-disposition'));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($res->baseResponse->getFile()->getPathname()));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertStringContainsString('csv@example.com', $sheet);
        $this->assertStringContainsString('LAPORAN DATA PENGGUNA', $sheet);
        // Nama berawalan "=" disimpan sebagai teks, bukan rumus.
        $this->assertStringContainsString('HYPERLINK', $sheet);
        $this->assertStringNotContainsString('<f>', $sheet);

        $this->actingAs(User::factory()->create(['role' => 'staff']))->get('/admin/pengguna/ekspor')->assertForbidden();
    }

    public function test_masuk_sebagai_pengguna_dicatat_dan_membatasi_aksi_berbahaya(): void
    {
        $admin = $this->admin();
        $u = User::factory()->create(['name' => 'Korban Uji']);

        $this->actingAs($admin)->post(route('admin.users.impersonate', $u))->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->get('/dashboard')->assertOk()->assertSee('Kamu sedang masuk sebagai Korban Uji');

        $this->put(route('account.settings.password'), ['current_password' => 'password', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345'])
            ->assertSessionHas('error');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $u->fresh()->password));
        $this->get('/admin')->assertForbidden();

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users.show', $u));
        $this->assertAuthenticatedAs($admin);
        $this->assertSame(2, \App\Models\AuditLog::where('action', 'user.impersonated')->count());

        // Admin dan staf tidak bisa dibuka lewat fitur ini.
        $staff = User::factory()->create(['role' => 'staff']);
        $this->post(route('admin.users.impersonate', $staff))->assertSessionHas('error');
    }

    public function test_halaman_detail_pengguna_menampilkan_panel_baru(): void
    {
        $this->seed(ExamSeeder::class);
        $u = User::factory()->create();
        $this->actingAs($this->admin())->get(route('admin.users.show', $u))->assertOk()
            ->assertSee('Keamanan dan akses')->assertSee('Masuk sebagai pengguna')->assertSee('Beri Premium')->assertSee('Pemakaian hari ini')->assertSee('Aktivitas akun');
    }

    public function test_admin_mengatur_akses_staf_dari_pengaturan(): void
    {
        $this->seed(ExamSeeder::class);
        $admin = $this->admin();
        $staff = User::factory()->create(['role' => 'staff']);

        // Bawaan: staf tidak boleh membuka pesanan dan jenis ujian.
        $this->assertFalse($staff->canAccess('orders'));
        $this->actingAs($staff)->get('/admin/pesanan')->assertForbidden();

        $this->actingAs($admin)->put(route('admin.settings.staff'), ['areas' => ['questions', 'orders', 'users', 'system']])->assertSessionHas('status');
        $staff = $staff->fresh();
        $this->assertTrue($staff->canAccess('orders'));
        $this->assertTrue($staff->canAccess('questions'));
        $this->assertFalse($staff->canAccess('careers'));   // dicabut
        $this->assertFalse($staff->canAccess('users'));     // wilayah admin, tidak bisa diberikan
        $this->assertFalse($staff->canAccess('system'));
        $this->assertTrue($staff->canAccess('dashboard'));  // selalu ada

        $this->actingAs($staff)->get('/admin/pesanan')->assertOk();
        $this->get('/admin/karier')->assertForbidden();
        $this->get('/admin/pengguna')->assertForbidden();
    }

    public function test_pengaturan_fitur_menimpa_config_dan_tampil_pengumuman(): void
    {
        $admin = $this->admin();
        $payload = [
            'learning_open' => '1', 'registration_open' => '1',
            'chatbot_free_daily' => 3, 'chatbot_premium_daily' => 99, 'interview_free_daily' => 2, 'interview_premium_daily' => 6,
            'cv_rewrite_free_daily' => 1, 'cv_rewrite_premium_daily' => 4, 'cover_letter_free_daily' => 1, 'cover_letter_premium_daily' => 4,
            'exam_pass_price' => 15000, 'exam_pass_valid_days' => 30, 'exam_trial_questions' => 7,
            'announcement_enabled' => '1', 'announcement_text' => 'Simulasi baru sudah rilis', 'announcement_tone' => 'success',
        ];
        $this->actingAs($admin)->put(route('admin.settings.features'), $payload)->assertSessionHasNoErrors();

        $this->assertSame(3, config('platform.chatbot.free_daily'));
        $this->assertSame(99, config('platform.chatbot.premium_daily'));
        $this->assertSame(15000, config('platform.exam.pass_price'));
        $this->assertSame(30, config('platform.exam.pass_valid_days'));
        $this->assertSame(7, config('platform.exam.trial_questions_per_segment'));

        $this->get('/admin')->assertOk()->assertSee('Simulasi baru sudah rilis');
        $this->get('/admin/pengaturan')->assertOk()->assertSee('Fitur dan kuota');

        // Staf tidak boleh mengubah pengaturan sistem.
        $this->actingAs(User::factory()->create(['role' => 'staff']))->put(route('admin.settings.features'), $payload)->assertForbidden();
    }

    public function test_masa_berlaku_tiket_bisa_dikosongkan_kembali(): void
    {
        $admin = $this->admin();
        $payload = [
            'learning_open' => '1', 'registration_open' => '1', 'chatbot_free_daily' => 10, 'chatbot_premium_daily' => 50,
            'interview_free_daily' => 1, 'interview_premium_daily' => 5, 'cv_rewrite_free_daily' => 1, 'cv_rewrite_premium_daily' => 5,
            'cover_letter_free_daily' => 1, 'cover_letter_premium_daily' => 5, 'exam_pass_price' => 9999, 'exam_trial_questions' => 5,
        ];
        $this->actingAs($admin)->put(route('admin.settings.features'), $payload + ['exam_pass_valid_days' => 30]);
        $this->assertSame(30, config('platform.exam.pass_valid_days'));
        $this->put(route('admin.settings.features'), $payload + ['exam_pass_valid_days' => '']);
        $this->assertNull(config('platform.exam.pass_valid_days'));
        $this->assertNull(app(SiteSettings::class)->get('exam_pass_valid_days'));
    }

    public function test_admin_mengubah_paket_dan_melihat_log_aktivitas(): void
    {
        $this->seed(ExamSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $admin = $this->admin();
        $plan = Plan::orderBy('price')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.plans.edit', $plan))->assertOk();
        $this->put(route('admin.plans.update', $plan), ['name' => 'Premium Baru', 'price' => 55000, 'interval' => 'month', 'features' => "Satu\nDua", 'is_active' => '1'])
            ->assertRedirect();
        $plan->refresh();
        $this->assertSame(55000, (int) $plan->price);
        $this->assertSame(['Satu', 'Dua'], $plan->features);

        $this->get('/admin/log-pembayaran')->assertNotFound();
        $this->get('/admin/skill')->assertNotFound();
        $this->get('/admin/log-aktivitas')->assertOk()->assertSee('Paket/harga diubah');
        $this->get('/admin/log-aktivitas?grup=settings&cari=Admin')->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'staff']))->get('/admin/log-aktivitas')->assertForbidden();
        $this->get(route('admin.plans.edit', $plan))->assertForbidden();
    }

    public function test_perubahan_pengguna_oleh_admin_tercatat_dan_sandi_admin_memaksa_ganti(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Staf Baru', 'email' => 'staf@example.com', 'password' => 'rahasia-123', 'role' => 'staff', 'force_change' => '1'])
            ->assertRedirect();
        $u = User::where('email', 'staf@example.com')->firstOrFail();
        $this->assertTrue($u->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'subject_id' => $u->id]);

        $this->put(route('admin.users.update', $u), ['name' => 'Staf Baru', 'email' => 'ganti@example.com', 'role' => 'member']);
        $u->refresh();
        $this->assertNull($u->email_verified_at); // email baru harus diverifikasi lagi
        $this->assertSame('member', $u->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.updated', 'subject_id' => $u->id]);

        $this->delete(route('admin.users.destroy', $u));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted', 'subject_label' => 'ganti@example.com']);
    }
}
