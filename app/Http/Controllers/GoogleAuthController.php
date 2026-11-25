<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CompletesLogin;
use App\Models\User;
use App\Services\Account\SessionManager;
use App\Services\Audit\Audit;
use App\Services\Auth\GoogleOAuth;
use App\Services\Settings\SiteSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Masuk atau daftar dengan Google. Email dari Google yang bertanda terverifikasi otomatis menjadi email terverifikasi. */
class GoogleAuthController extends Controller
{
    use CompletesLogin;

    public function __construct(private GoogleOAuth $google, private SiteSettings $settings)
    {
    }

    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->google->enabled()) {
            return $this->fail('Masuk dengan Google belum diaktifkan.');
        }

        $flow = $this->google->begin();
        $request->session()->put('google.oauth', [
            'state' => $flow['state'],
            'verifier' => $flow['verifier'],
            'expires' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->away($flow['url']);
    }

    public function callback(Request $request, SessionManager $sessions): RedirectResponse
    {
        // Sekali pakai: state dan verifier dibuang apa pun hasilnya.
        $flow = $request->session()->pull('google.oauth');

        if (! $this->google->enabled()) {
            return $this->fail('Masuk dengan Google belum diaktifkan.');
        }
        if ($request->query('error')) {
            return $this->fail('Masuk dengan Google dibatalkan.');
        }
        if (! is_array($flow) || ($flow['expires'] ?? 0) < now()->timestamp
            || ! hash_equals((string) ($flow['state'] ?? ''), (string) $request->query('state'))) {
            return $this->fail('Sesi masuk dengan Google sudah berakhir. Coba lagi.');
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return $this->fail('Google tidak mengirim kode masuk. Coba lagi.');
        }

        try {
            $profile = $this->google->profile($code, (string) $flow['verifier']);
        } catch (\Throwable $e) {
            report($e);
            $profile = null;
        }
        if (! $profile) {
            return $this->fail('Google tidak bisa memverifikasi akunmu saat ini. Coba lagi sebentar lagi.');
        }

        // Hanya email yang dijamin Google milik penggunanya yang boleh dipercaya.
        if (! $profile['email_verified']) {
            return $this->fail('Email akun Google itu belum terverifikasi oleh Google, jadi tidak bisa dipakai untuk masuk.');
        }

        $user = User::where('google_id', $profile['sub'])->first();

        if (! $user) {
            $user = User::where('email', $profile['email'])->first();

            if ($user) {
                if ($user->google_id !== null) {
                    return $this->fail('Email ini sudah tertaut ke akun Google lain.');
                }
                if ($user->isInternal()) {
                    return $this->fail('Akun admin dan staf masuk dengan email dan kata sandi.');
                }
                if ($user->isSuspended()) {
                    return $this->fail('Akunmu sedang ditangguhkan. Hubungi admin bila kamu merasa ini keliru.');
                }

                $this->link($user, $profile['sub'], $sessions);
            } else {
                if (! $this->settings->registrationOpen()) {
                    return $this->fail('Pendaftaran akun baru sedang ditutup sementara.');
                }

                try {
                    $user = $this->createUser($profile);
                } catch (UniqueConstraintViolationException) {
                    return $this->fail('Akun dengan email ini baru saja dibuat. Coba masuk lagi.');
                }
            }
        }

        if ($user->isInternal()) {
            return $this->fail('Akun admin dan staf masuk dengan email dan kata sandi.');
        }
        if ($user->isSuspended()) {
            Audit::log('login.suspended', $user, [], $user);

            return $this->fail('Akunmu sedang ditangguhkan. Hubungi admin bila kamu merasa ini keliru.');
        }

        // Google tidak melewati verifikasi 2 langkah milik kita.
        if ($user->hasTwoFactor()) {
            $request->session()->put('login.2fa', ['id' => $user->id, 'remember' => false, 'expires' => now()->addMinutes(5)->timestamp]);

            return redirect()->route('login.2fa');
        }

        return $this->completeLogin($request, $user, false);
    }

    /**
     * Tautkan akun email+sandi yang sudah ada ke Google. Bila emailnya belum terverifikasi, akun itu bisa saja dibuat
     * orang lain dengan email ini (sebelum pemilik aslinya datang), jadi sandi lama dibuang dan semua sesi dicabut.
     * Pemilik asli sudah terbukti lewat Google; ia bisa membuat sandi baru lewat "Lupa kata sandi".
     */
    private function link(User $user, string $sub, SessionManager $sessions): void
    {
        $unverified = ! $user->hasVerifiedEmail();

        $user->forceFill(['google_id' => $sub]);
        if ($unverified) {
            $user->forceFill(['email_verified_at' => now(), 'password' => Str::random(40), 'must_change_password' => false]);
        }
        $user->save();

        if ($unverified) {
            $sessions->revokeOthers($user, '');
        }
    }

    /** @param array{sub:string,email:string,name:string} $profile */
    private function createUser(array $profile): User
    {
        $user = new User([
            'name' => Str::limit($profile['name'], 80, ''),
            'email' => $profile['email'],
            'password' => Str::random(40), // tidak pernah dipakai; masuk lewat Google atau "Lupa kata sandi"
        ]);
        $user->forceFill(['google_id' => $profile['sub'], 'email_verified_at' => now()])->save();

        return $user;
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('error', $message);
    }
}
