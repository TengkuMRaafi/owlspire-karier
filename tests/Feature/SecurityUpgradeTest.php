<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ResetPasswordId;
use App\Notifications\VerifyEmailId;
use App\Services\Security\Totp;
use App\Services\Security\TwoFactor;
use App\Services\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/** Keamanan akun: lupa sandi, verifikasi email, kunci login, penangguhan, 2 langkah, dan wajib ganti sandi. */
class SecurityUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_totp_sesuai_vektor_uji_rfc6238(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');
        $this->assertSame('287082', Totp::code($secret, 59));
        $this->assertSame('081804', Totp::code($secret, 1111111109));
        $this->assertNotNull(Totp::verify($secret, Totp::code($secret, 1234567890), 1, 1234567890));
        $this->assertNull(Totp::verify($secret, '000000', 1, 1234567890));
    }

    public function test_lupa_sandi_kirim_email_dan_respons_sama_untuk_email_tak_dikenal(): void
    {
        Notification::fake();
        $u = User::factory()->create(['email' => 'ada@example.com']);

        $this->get('/lupa-sandi')->assertOk()->assertSee('Lupa kata sandi');
        $a = $this->post('/lupa-sandi', ['email' => 'ada@example.com']);
        $b = $this->post('/lupa-sandi', ['email' => 'tidak-ada@example.com']);
        $a->assertSessionHas('status');
        $this->assertSame(session('status'), $a->getSession()->get('status'));
        $b->assertSessionHas('status');
        Notification::assertSentTo($u, ResetPasswordId::class);
        Notification::assertNothingSentTo(new User(['email' => 'tidak-ada@example.com']));
    }

    public function test_atur_ulang_sandi_lewat_token_mengganti_sandi_dan_memverifikasi_email(): void
    {
        $u = User::factory()->unverified()->create(['email' => 'lupa@example.com']);
        $token = Password::createToken($u);

        $this->post('/atur-ulang-sandi', ['token' => $token, 'email' => $u->email, 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->post('/atur-ulang-sandi', ['token' => 'salah', 'email' => $u->email, 'password' => 'sandi-baru-123', 'password_confirmation' => 'sandi-baru-123'])
            ->assertSessionHasErrors('email');

        $this->post('/atur-ulang-sandi', ['token' => $token, 'email' => $u->email, 'password' => 'sandi-baru-123', 'password_confirmation' => 'sandi-baru-123'])
            ->assertRedirect('/masuk');

        $u->refresh();
        $this->assertTrue(Hash::check('sandi-baru-123', $u->password));
        $this->assertNotNull($u->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'password.reset', 'actor_id' => $u->id]);
        $this->post('/masuk', ['email' => $u->email, 'password' => 'sandi-baru-123'])->assertRedirect('/dashboard');
    }

    public function test_daftar_mengirim_verifikasi_email_dan_sandi_harus_huruf_dan_angka(): void
    {
        Notification::fake();
        $this->post('/daftar', ['name' => 'Baru', 'email' => 'baru@example.com', 'password' => 'hurufsaja', 'password_confirmation' => 'hurufsaja'])
            ->assertSessionHasErrors('password');

        $this->post('/daftar', ['name' => 'Baru', 'email' => 'baru@example.com', 'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123'])
            ->assertRedirect(route('verification.sent'));
        $u = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertNull($u->email_verified_at);
        $this->assertGuest();
        Notification::assertSentTo($u, VerifyEmailId::class);
    }

    public function test_login_dikunci_setelah_terlalu_banyak_percobaan_salah(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $u = User::factory()->create(['email' => 'kunci@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => $u->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        }
        // Percobaan ke-6 ditolak walau sandinya benar.
        $this->post('/masuk', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'login.locked']);
    }

    public function test_akun_ditangguhkan_tidak_bisa_masuk_dan_langsung_terkeluar(): void
    {
        $u = User::factory()->create(['email' => 'tangguh@example.com']);
        $u->forceFill(['suspended_at' => now(), 'suspended_reason' => 'uji'])->save();

        $this->post('/masuk', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($u)->get('/dashboard')->assertRedirect('/masuk');
    }

    public function test_masuk_mencatat_login_terakhir(): void
    {
        $u = User::factory()->create(['email' => 'jejak@example.com']);
        $this->post('/masuk', ['email' => $u->email, 'password' => 'password']);
        $this->assertNotNull($u->fresh()->last_login_at);
    }

    public function test_verifikasi_dua_langkah_aktif_lalu_dipakai_saat_masuk(): void
    {
        $u = User::factory()->create(['email' => 'dua@example.com']);
        $tf = app(TwoFactor::class);

        $this->actingAs($u)->post(route('account.settings.2fa.begin'))->assertRedirect();
        $u->refresh();
        $this->post(route('account.settings.2fa.confirm'), ['code' => '000000'])->assertSessionHas('error');
        $this->post(route('account.settings.2fa.confirm'), ['code' => Totp::code($u->two_factor_secret)])->assertSessionHas('recovery_codes');
        $u->refresh();
        $this->assertTrue($tf->enabled($u));
        $this->post('/keluar');

        // Sandi benar belum cukup: diarahkan ke langkah kedua.
        $this->post('/masuk', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/masuk/2fa');
        $this->assertGuest();
        $this->post('/masuk/2fa', ['code' => '111111'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // Kode yang sama dengan langkah waktu terpakai tidak bisa dipakai ulang; pakai kode pemulihan.
        $recovery = $tf->regenerateRecovery($u->fresh());
        $this->post('/masuk/2fa', ['code' => $recovery[0]])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->assertSame(7, $tf->recoveryLeft($u->fresh()));
    }

    public function test_wajib_ganti_sandi_mengunci_ke_halaman_ganti_sandi(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['must_change_password' => true])->save();

        $this->actingAs($u)->get('/dashboard')->assertRedirect('/ganti-sandi');
        $this->get('/ganti-sandi')->assertOk();
        $this->put('/ganti-sandi', ['current_password' => 'password', 'password' => 'sandi-baru-456', 'password_confirmation' => 'sandi-baru-456'])
            ->assertRedirect();
        $this->assertFalse($u->fresh()->must_change_password);
        $this->get('/dashboard')->assertOk();
    }

    public function test_ganti_sandi_akun_tercatat_di_log(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->put(route('account.settings.password'), ['current_password' => 'password', 'password' => 'baru-12345', 'password_confirmation' => 'baru-12345'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('audit_logs', ['action' => 'password.changed', 'actor_id' => $u->id]);
    }

    public function test_pendaftaran_bisa_ditutup_admin_dan_mode_pemeliharaan_menutup_pengunjung(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('admin.settings.features'), $this->featureForm(['registration_open' => null, 'maintenance_enabled' => '1', 'maintenance_message' => 'Sedang upgrade']))
            ->assertSessionHasNoErrors();
        $this->post('/keluar');

        $this->get('/')->assertStatus(503)->assertSee('Sedang upgrade');
        $this->get('/masuk')->assertOk(); // admin tetap bisa masuk
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Mode pemeliharaan sedang aktif');
        $this->app['auth']->forgetGuards();
        app(SiteSettings::class)->put('maintenance', ['enabled' => false, 'message' => '']);

        $this->get('/daftar')->assertOk()->assertSee('Pendaftaran ditutup');
        $this->post('/daftar', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123'])->assertRedirect('/daftar');
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    /** @param array<string,mixed> $over */
    private function featureForm(array $over = []): array
    {
        $f = [
            'registration_open' => '1', 'learning_open' => '1',
            'chatbot_free_daily' => 10, 'chatbot_premium_daily' => 50, 'interview_free_daily' => 1, 'interview_premium_daily' => 5,
            'cv_rewrite_free_daily' => 1, 'cv_rewrite_premium_daily' => 5, 'cover_letter_free_daily' => 1, 'cover_letter_premium_daily' => 5,
            'exam_pass_price' => 9999, 'exam_pass_valid_days' => '', 'exam_trial_questions' => 5,
        ];

        return array_filter($over + $f, fn ($v) => $v !== null);
    }
}
