<?php

namespace Tests\Feature;

use App\Models\Entitlement;
use App\Models\User;
use App\Services\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'cid.apps.googleusercontent.com', 'services.google.client_secret' => 'secret', 'services.google.redirect' => null]);
    }

    /** Mulai alur dari tombol, lalu kembalikan state yang dikirim ke Google. */
    private function start(): string
    {
        $res = $this->get('/masuk/google');
        $res->assertRedirect();
        parse_str((string) parse_url($res->headers->get('Location'), PHP_URL_QUERY), $q);

        return $q['state'];
    }

    private function fakeGoogle(array $profile = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok-123', 'token_type' => 'Bearer']),
            'openidconnect.googleapis.com/*' => Http::response($profile + [
                'sub' => 'g-1001', 'email' => 'Budi@Example.com', 'email_verified' => true, 'name' => 'Budi Santoso',
            ]),
        ]);
    }

    private function kembali(string $state, string $code = 'kode-ok')
    {
        return $this->get('/masuk/google/callback?'.http_build_query(['state' => $state, 'code' => $code]));
    }

    public function test_tombol_tampil_hanya_bila_kunci_google_terisi(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Masuk dengan Google')->assertSee(route('login.google'), false);
        $this->get('/daftar')->assertOk()->assertSee('Daftar dengan Google');

        config(['services.google.client_secret' => null]);
        $this->get('/masuk')->assertOk()->assertDontSee('Masuk dengan Google');
        $this->get('/masuk/google')->assertRedirect(route('login'))->assertSessionHas('error');
    }

    public function test_pengalihan_ke_google_memakai_state_dan_pkce(): void
    {
        $res = $this->get('/masuk/google');
        $loc = $res->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $loc);
        parse_str((string) parse_url($loc, PHP_URL_QUERY), $q);
        $this->assertSame('cid.apps.googleusercontent.com', $q['client_id']);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame(route('login.google.callback'), $q['redirect_uri']);
        $this->assertSame(40, strlen($q['state']));
        $this->assertStringContainsString('email', $q['scope']);
    }

    public function test_akun_baru_dibuat_terverifikasi_dan_langsung_masuk(): void
    {
        $this->fakeGoogle();
        $state = $this->start();

        $this->kembali($state)->assertRedirect(route('dashboard.index'));

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('g-1001', $user->google_id);
        $this->assertSame('member', $user->role);
        $this->assertSame('Budi Santoso', $user->name);
    }

    public function test_email_yang_belum_diverifikasi_google_ditolak(): void
    {
        $this->fakeGoogle(['email_verified' => false]);
        $state = $this->start();

        $this->kembali($state)->assertRedirect(route('login'))->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_email_verified_berupa_string_selain_true_ditolak(): void
    {
        $this->fakeGoogle(['email_verified' => 'false']);
        $this->kembali($this->start())->assertRedirect(route('login'));
        $this->assertGuest();

        $this->fakeGoogle(['email_verified' => 1]);
        $this->kembali($this->start())->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_state_salah_atau_tanpa_alur_ditolak_dan_tidak_menghubungi_google(): void
    {
        $this->fakeGoogle();
        $this->start();

        $this->kembali('state-palsu')->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();

        // Tanpa memulai dari tombol sama sekali.
        $this->flushSession();
        $this->kembali('apa-saja')->assertRedirect(route('login'));
        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_state_hanya_bisa_dipakai_sekali(): void
    {
        $this->fakeGoogle();
        $state = $this->start();

        $this->kembali($state)->assertRedirect(route('dashboard.index'));
        auth()->logout();
        $this->flushSession();

        $this->kembali($state)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pengguna_membatalkan_di_google(): void
    {
        $state = $this->start();

        $this->get('/masuk/google/callback?error=access_denied&state='.$state)->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_google_menolak_kode_penukaran(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

        $this->kembali($this->start())->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_akun_lama_terverifikasi_ditautkan_tanpa_mengubah_sandi(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com', 'password' => 'rahasia123']);
        $this->fakeGoogle();

        $this->kembali($this->start())->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1001', $user->fresh()->google_id);
        $this->assertTrue(Hash::check('rahasia123', $user->fresh()->password));
    }

    public function test_akun_lama_belum_terverifikasi_ditautkan_tapi_sandi_lama_dibuang_dan_sesi_dicabut(): void
    {
        // Skenario pembajakan awal: orang lain mendaftar dengan email korban dan sandinya sendiri, lalu korban masuk dengan Google.
        config(['session.driver' => 'database']);
        $user = User::factory()->unverified()->create(['email' => 'budi@example.com', 'password' => 'sandi-penyerang']);
        DB::table('sessions')->insert(['id' => 'sesi-penyerang', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $oldRemember = $user->remember_token;
        $this->fakeGoogle();

        $this->kembali($this->start())->assertRedirect(route('dashboard.index'));

        $fresh = $user->fresh();
        $this->assertAuthenticatedAs($fresh);
        $this->assertNotNull($fresh->email_verified_at);
        $this->assertSame('g-1001', $fresh->google_id);
        $this->assertFalse(Hash::check('sandi-penyerang', $fresh->password));
        $this->assertNotSame($oldRemember, $fresh->remember_token);
        $this->assertSame(0, DB::table('sessions')->where('id', 'sesi-penyerang')->count());
    }

    public function test_masuk_lagi_lewat_google_id_walau_email_google_berubah(): void
    {
        $user = User::factory()->create(['email' => 'lama@example.com']);
        $user->forceFill(['google_id' => 'g-1001'])->save();
        $this->fakeGoogle(['email' => 'baru@example.com']);

        $this->kembali($this->start())->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
        $this->assertSame('lama@example.com', $user->fresh()->email);
    }

    public function test_email_yang_sudah_tertaut_ke_google_lain_ditolak(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com']);
        $user->forceFill(['google_id' => 'g-orang-lain'])->save();
        $this->fakeGoogle();

        $this->kembali($this->start())->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_admin_dan_staf_tidak_bisa_masuk_lewat_google(): void
    {
        foreach (['admin', 'staff'] as $i => $role) {
            $u = User::factory()->create(['email' => "{$role}@example.com", 'role' => $role]);
            $this->fakeGoogle(['sub' => "g-{$role}", 'email' => $u->email]);

            $this->kembali($this->start())->assertRedirect(route('login'))->assertSessionHas('error');
            $this->assertGuest();
            $this->assertNull($u->fresh()->google_id);
        }
    }

    public function test_akun_ditangguhkan_ditolak(): void
    {
        $u = User::factory()->create(['email' => 'budi@example.com']);
        $u->forceFill(['suspended_at' => now()])->save();
        $this->fakeGoogle();

        $this->kembali($this->start())->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
        $this->assertNull($u->fresh()->google_id);
    }

    public function test_akun_dengan_2fa_tetap_diminta_kode_dan_belum_masuk(): void
    {
        $u = User::factory()->create(['email' => 'budi@example.com']);
        $u->forceFill(['two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()])->save();
        $this->fakeGoogle();

        $this->kembali($this->start())->assertRedirect(route('login.2fa'));

        $this->assertGuest();
    }

    public function test_pendaftaran_ditutup_menolak_akun_baru_tapi_akun_lama_tetap_bisa_masuk(): void
    {
        app(SiteSettings::class)->put('registration_open', false);
        $this->fakeGoogle();

        $this->kembali($this->start())->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
        $this->assertSame(0, User::count());

        $existing = User::factory()->create(['email' => 'budi@example.com']);
        $this->kembali($this->start())->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticatedAs($existing);
    }

    public function test_pengguna_google_baru_bisa_memakai_fitur_premium_dan_checkout(): void
    {
        $this->fakeGoogle();
        $this->kembali($this->start());
        $user = User::where('email', 'budi@example.com')->firstOrFail();
        Entitlement::create(['user_id' => $user->id, 'type' => 'subscription', 'expires_at' => now()->addMonth()]);

        $this->get('/dashboard/rencana')->assertOk()->assertDontSee('khusus Premium');
    }
}
