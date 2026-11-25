<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/** Pengguna biasa wajib Google. Admin dan staf tetap email + sandi. */
class GoogleOnlyMembersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'cid', 'services.google.client_secret' => 'secret', 'platform.auth.members_google_only' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_halaman_daftar_hanya_menawarkan_google(): void
    {
        $this->get('/daftar')->assertOk()
            ->assertSee('Daftar dengan Google')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('password_confirmation', false);
    }

    public function test_kirim_form_daftar_manual_ditolak_dan_tidak_membuat_akun(): void
    {
        $this->post('/daftar', ['name' => 'Budi', 'email' => 'budi@example.com', 'password' => 'rahasia123A', 'password_confirmation' => 'rahasia123A'])
            ->assertRedirect(route('register'))->assertSessionHas('error');

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_pengguna_biasa_tidak_bisa_masuk_dengan_sandi_walau_benar(): void
    {
        $u = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/masuk', ['email' => $u->email, 'password' => 'rahasia123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pesan_gagal_sama_untuk_email_yang_tidak_terdaftar(): void
    {
        $u = User::factory()->create(['password' => 'rahasia123']);

        $a = $this->post('/masuk', ['email' => $u->email, 'password' => 'rahasia123'])->assertSessionHasErrors('email')->getSession()->get('errors')->first('email');
        $this->flushSession();
        $b = $this->post('/masuk', ['email' => 'tidak-ada@example.com', 'password' => 'apa-saja'])->assertSessionHasErrors('email')->getSession()->get('errors')->first('email');

        $this->assertSame($a, $b);
    }

    public function test_admin_dan_staf_tetap_masuk_dengan_sandi(): void
    {
        $staf = User::factory()->create(['role' => 'staff', 'password' => 'rahasia123']);

        $this->post('/masuk', ['email' => $staf->email, 'password' => 'rahasia123'])->assertRedirect();
        $this->assertAuthenticatedAs($staf);
    }

    public function test_halaman_masuk_menaruh_google_di_depan_dan_form_sandi_untuk_admin_staf(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Masuk dengan Google')->assertSee('Admin atau staf?');
    }

    public function test_tanpa_kunci_google_semua_berjalan_seperti_biasa_agar_tidak_terkunci(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        $u = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/masuk', ['email' => $u->email, 'password' => 'rahasia123'])->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticatedAs($u);

        auth()->logout();
        $this->flushSession();
        $this->post('/daftar', ['name' => 'Siti', 'email' => 'siti@example.com', 'password' => 'rahasia123A', 'password_confirmation' => 'rahasia123A'])
            ->assertRedirect(route('verification.sent')); // daftar manual kini wajib verifikasi email dulu
    }

    public function test_aturan_bisa_dimatikan_lewat_config(): void
    {
        config(['platform.auth.members_google_only' => false]);
        $u = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/masuk', ['email' => $u->email, 'password' => 'rahasia123'])->assertRedirect(route('dashboard.index'));
    }

    public function test_lupa_sandi_hanya_mengirim_ke_admin_dan_staf_tapi_responsnya_sama(): void
    {
        Notification::fake();
        $member = User::factory()->create();
        $staf = User::factory()->create(['role' => 'staff']);

        $a = $this->post('/lupa-sandi', ['email' => $member->email])->assertSessionHas('status')->getSession()->get('status');
        Notification::assertNothingSent();

        $b = $this->post('/lupa-sandi', ['email' => $staf->email])->assertSessionHas('status')->getSession()->get('status');
        Notification::assertSentTo($staf, \App\Notifications\ResetPasswordId::class);

        $this->assertSame($a, $b);
    }

    public function test_atur_ulang_sandi_ditolak_untuk_pengguna_biasa_walau_token_valid(): void
    {
        $member = User::factory()->create(['password' => 'lama-12345A']);
        $token = Password::createToken($member);

        $this->post('/atur-ulang-sandi', ['token' => $token, 'email' => $member->email, 'password' => 'baru-12345A', 'password_confirmation' => 'baru-12345A'])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('lama-12345A', $member->fresh()->password));
    }

    public function test_pengaturan_pengguna_google_tidak_meminta_kata_sandi(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->get(route('account.settings'))->assertOk()
            ->assertSee('Masuk dengan Google')
            ->assertDontSee('Ganti kata sandi')
            ->assertDontSee('Kata sandi saat ini');
    }

    public function test_ganti_sandi_dan_mulai_2fa_ditolak_untuk_pengguna_google(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->put(route('account.settings.password'), ['current_password' => 'password', 'password' => 'baru-12345A', 'password_confirmation' => 'baru-12345A'])->assertForbidden();
        $this->post(route('account.settings.2fa.begin'))->assertForbidden();
    }

    public function test_keluar_dari_perangkat_lain_tanpa_sandi(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->post(route('account.settings.devices.logout'))->assertSessionHasNoErrors()->assertSessionHas('status');
    }

    public function test_hapus_akun_pengguna_google_dengan_mengetik_email(): void
    {
        $u = User::factory()->create(['email' => 'budi@example.com']);

        $this->actingAs($u)->delete(route('account.settings.destroy'), ['confirm_email' => 'salah@example.com', 'confirm' => '1'])->assertSessionHasErrors('confirm_email');
        $this->assertNotNull($u->fresh());

        $this->delete(route('account.settings.destroy'), ['confirm_email' => 'Budi@Example.com', 'confirm' => '1'])->assertRedirect(route('home'));
        $this->assertNull(User::find($u->id));
    }

    public function test_staf_tetap_melihat_form_sandi_di_pengaturan(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staf)->get(route('account.settings'))->assertOk()->assertSee('Ganti kata sandi')->assertSee('Kata sandi saat ini');
    }

    public function test_admin_hanya_bisa_membuat_akun_staf_bukan_pengguna_biasa(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Pengguna', 'email' => 'user@example.com', 'password' => 'rahasia-123', 'role' => 'member'])
            ->assertSessionHasErrors('role');
        $this->assertNull(User::where('email', 'user@example.com')->first());

        $this->post(route('admin.users.store'), ['name' => 'Staf Baru', 'email' => 'staf@example.com', 'password' => 'rahasia-123', 'role' => 'staff'])->assertRedirect();
        $this->assertSame('staff', User::where('email', 'staf@example.com')->value('role'));
    }
}
