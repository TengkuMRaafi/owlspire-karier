<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CompletesLogin;
use App\Models\User;
use App\Services\Audit\Audit;
use App\Services\Auth\GoogleOAuth;
use App\Services\Security\Turnstile;
use App\Services\Settings\SiteSettings;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use CompletesLogin;

    /** Hash bcrypt palsu: dipakai agar waktu respons sama baik email terdaftar maupun tidak. */
    private const DUMMY_HASH = '$2y$12$CEOACLCGCCEy5euBoZWDR.oQJNW6pQ8XTweTprfqjL5xPJ7Kqxr6K';

    public function __construct(private SiteSettings $settings, private Turnstile $turnstile)
    {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        if (! $this->settings->registrationOpen()) {
            return view('auth.register-closed');
        }

        return view('auth.register', ['googleOnly' => app(GoogleOAuth::class)->membersGoogleOnly()]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (! $this->turnstile->passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withErrors(['email' => 'Verifikasi keamanan gagal. Muat ulang halaman lalu coba lagi.'])->onlyInput('email');
        }

        $email = Str::lower(trim($credentials['email']));
        $pairKey = 'login:'.sha1($email.'|'.$request->ip());
        $emailKey = 'login-email:'.sha1($email);
        $lock = (int) config('security.login_lock_minutes', 15);

        foreach ([[$pairKey, config('security.login_max_attempts')], [$emailKey, config('security.login_max_attempts_email')]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, (int) $max)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

                return back()->withErrors(['email' => "Terlalu banyak percobaan masuk. Coba lagi sekitar {$minutes} menit, atau atur ulang kata sandimu."])->onlyInput('email');
            }
        }

        $provider = Auth::guard('web')->getProvider();
        $user = $provider->retrieveByCredentials(['email' => $credentials['email']]);
        $valid = $user instanceof User && $provider->validateCredentials($user, ['password' => $credentials['password']]);
        // Pengguna biasa masuk lewat Google. Sandi yang benar pun ditolak untuk mereka, dengan pesan yang sama seperti gagal biasa
        // supaya email terdaftar tidak bisa ditebak.
        $googleOnly = app(GoogleOAuth::class)->membersGoogleOnly();
        if ($valid && $googleOnly && ! $user->isInternal()) {
            $valid = false;
        }
        if (! $user) {
            Hash::check($credentials['password'], self::DUMMY_HASH);
        }

        if (! $valid) {
            RateLimiter::hit($pairKey, $lock * 60);
            RateLimiter::hit($emailKey, $lock * 60);
            if ($user instanceof User && $user->isInternal()) {
                Audit::log('login.failed', $user, [], $user);
            }
            if (RateLimiter::tooManyAttempts($pairKey, (int) config('security.login_max_attempts')) && $user instanceof User) {
                Audit::log('login.locked', $user, [], $user);
            }

            return back()->withErrors(['email' => $googleOnly
                ? 'Email atau kata sandi salah. Pengguna biasa masuk dengan tombol Google; kata sandi hanya untuk admin dan staf.'
                : 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        if ($user->isSuspended()) {
            Audit::log('login.suspended', $user, [], $user);

            return back()->withErrors(['email' => 'Akunmu sedang ditangguhkan. Hubungi admin bila kamu merasa ini keliru.'])->onlyInput('email');
        }

        RateLimiter::clear($pairKey);

        // Pengguna biasa wajib verifikasi email lewat link sebelum bisa masuk (admin dan staf dikecualikan).
        if (! $user->skipsEmailVerification() && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.sent')
                ->with('verify_email', $user->email)
                ->with('error', 'Emailmu belum diverifikasi. Klik link di email yang kami kirim, atau kirim ulang linknya.');
        }

        if ($user->hasTwoFactor()) {
            $request->session()->put('login.2fa', ['id' => $user->id, 'remember' => $request->boolean('remember'), 'expires' => now()->addMinutes(5)->timestamp]);

            return redirect()->route('login.2fa');
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'));
    }

    public function register(Request $request): RedirectResponse
    {
        if (! $this->settings->registrationOpen()) {
            return redirect()->route('register')->with('error', 'Pendaftaran akun baru sedang ditutup sementara.');
        }

        if (app(GoogleOAuth::class)->membersGoogleOnly()) {
            return redirect()->route('register')->with('error', 'Pendaftaran dilakukan lewat tombol Daftar dengan Google.');
        }

        // Email yang pernah didaftarkan tapi belum diverifikasi (salah ketik, atau tak sempat klik link) boleh didaftarkan ulang:
        // pendaftar terakhir menang, jadi sandi lama dari pendaftaran yang tidak pernah diverifikasi tidak berlaku lagi.
        $typed = Str::lower(trim((string) $request->input('email')));
        $stale = $typed === '' ? null : User::whereRaw('lower(email) = ?', [$typed])
            ->whereNull('email_verified_at')->whereNull('google_id')->where('role', 'member')->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => array_values(array_filter(['required', 'email', 'max:255', $stale ? null : 'unique:users,email'])),
            'password' => ['required', 'confirmed', PasswordRules::default()],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
        ] + PasswordRules::messages());

        if (! $this->turnstile->passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withErrors(['email' => 'Verifikasi keamanan gagal. Muat ulang halaman lalu coba lagi.'])->onlyInput('name', 'email');
        }

        // Role tidak boleh datang dari form: pendaftar selalu "member" (nilai bawaan kolom).
        if ($stale) {
            // Akun hasil pembelian tanpa daftar yang didaftarkan manual: kini wajib verifikasi email seperti akun biasa.
            $stale->forceFill(['name' => $data['name'], 'password' => $data['password'], 'auto_password' => false, 'password_set_at' => now()])->save();
            $user = $stale;
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
        }

        // Belum masuk: akun aktif setelah link verifikasi di email diklik.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e); // gagal kirim email tidak boleh menggagalkan pendaftaran; pengguna bisa kirim ulang dari halaman berikutnya
        }

        return redirect()->route('verification.sent')->with('verify_email', $user->email);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
