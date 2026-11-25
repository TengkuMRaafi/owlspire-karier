<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Daftar dengan email + sandi (selain Google): wajib verifikasi lewat link sebelum bisa masuk. */
class EmailRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $over = []): array
    {
        return $over + ['name' => 'Rina', 'email' => 'rina@example.com', 'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123'];
    }

    public function test_halaman_daftar_dan_masuk_punya_form_email_walau_google_terpasang(): void
    {
        config(['services.google.client_id' => 'cid', 'services.google.client_secret' => 'secret']);

        $this->get('/daftar')->assertOk()
            ->assertSee('Daftar dengan Google')->assertSee('Daftar dengan email')->assertSee('password_confirmation', false);
        $this->get('/masuk')->assertOk()->assertSee('Masuk dengan Google')->assertSee('name="password"', false);
    }

    public function test_halaman_cek_email_tampil_setelah_daftar(): void
    {
        Notification::fake();

        $this->post('/daftar', $this->data())->assertRedirect(route('verification.sent'));
        $this->get(route('verification.sent'))->assertOk()->assertSee('rina@example.com');
    }

    public function test_halaman_cek_email_tanpa_data_diarahkan_ke_masuk(): void
    {
        $this->get(route('verification.sent'))->assertRedirect(route('login'));
    }

    public function test_belum_terverifikasi_tidak_bisa_masuk_walau_sandi_benar(): void
    {
        $u = User::factory()->unverified()->create(['password' => 'rahasia-123']);

        $this->post('/masuk', ['email' => $u->email, 'password' => 'rahasia-123'])->assertRedirect(route('verification.sent'));
        $this->assertGuest();
    }

    public function test_link_verifikasi_bekerja_tanpa_masuk_lalu_bisa_masuk(): void
    {
        $u = User::factory()->unverified()->create(['password' => 'rahasia-123']);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $u->id, 'hash' => sha1($u->email)]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertNotNull($u->fresh()->email_verified_at);

        $this->post('/masuk', ['email' => $u->email, 'password' => 'rahasia-123'])->assertRedirect();
        $this->assertAuthenticatedAs($u);
    }

    public function test_link_dengan_hash_salah_atau_tanpa_tanda_tangan_ditolak(): void
    {
        $u = User::factory()->unverified()->create();
        $bad = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $u->id, 'hash' => sha1('lain@example.com')]);

        $this->get($bad)->assertForbidden();
        $this->get(route('verification.verify', ['id' => $u->id, 'hash' => sha1($u->email)]))->assertForbidden();
        $this->assertNull($u->fresh()->email_verified_at);
    }

    public function test_kirim_ulang_responsnya_sama_untuk_email_terdaftar_dan_tidak(): void
    {
        Notification::fake();
        $u = User::factory()->unverified()->create(['email' => 'ada@example.com']);

        $a = $this->post(route('verification.resend'), ['email' => 'ada@example.com'])->assertRedirect(route('verification.sent'))->getSession()->get('status');
        $b = $this->post(route('verification.resend'), ['email' => 'tidak@example.com'])->assertRedirect(route('verification.sent'))->getSession()->get('status');

        $this->assertSame($a, $b);
        Notification::assertSentToTimes($u, VerifyEmailId::class, 1);
    }

    public function test_kirim_ulang_dibatasi_per_email(): void
    {
        Notification::fake();
        $u = User::factory()->unverified()->create(['email' => 'ada@example.com']);

        foreach (range(1, 5) as $i) {
            $this->post(route('verification.resend'), ['email' => 'ada@example.com']);
        }

        Notification::assertSentToTimes($u, VerifyEmailId::class, 3);
    }

    public function test_daftar_ulang_dengan_email_belum_terverifikasi_menimpa_sandi_lama(): void
    {
        Notification::fake();
        $old = User::factory()->unverified()->create(['email' => 'rina@example.com', 'password' => 'sandi-lama-1']);

        $this->post('/daftar', $this->data(['password' => 'sandi-baru-9', 'password_confirmation' => 'sandi-baru-9']))
            ->assertRedirect(route('verification.sent'));

        $this->assertSame(1, User::where('email', 'rina@example.com')->count());
        $this->assertTrue(Hash::check('sandi-baru-9', $old->fresh()->password));
        $this->assertFalse(Hash::check('sandi-lama-1', $old->fresh()->password));
    }

    public function test_daftar_dengan_email_yang_sudah_terverifikasi_tetap_ditolak(): void
    {
        User::factory()->create(['email' => 'rina@example.com', 'password' => 'sandi-lama-1']);

        $this->post('/daftar', $this->data())->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_staf_belum_terverifikasi_tetap_bisa_masuk(): void
    {
        $staff = User::factory()->unverified()->create(['role' => 'staff', 'password' => 'rahasia-123']);

        $this->post('/masuk', ['email' => $staff->email, 'password' => 'rahasia-123'])->assertRedirect();
        $this->assertAuthenticatedAs($staff);
    }
}
