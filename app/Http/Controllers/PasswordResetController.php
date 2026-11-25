<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Account\SessionManager;
use App\Services\Audit\Audit;
use App\Services\Auth\GoogleOAuth;
use App\Services\Security\Turnstile;
use App\Support\PasswordRules;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Lupa dan atur ulang kata sandi lewat link email. Respons permintaan selalu sama supaya email terdaftar tidak bisa ditebak. */
class PasswordResetController extends Controller
{
    private const SENT = 'Kalau email itu terdaftar, link atur ulang kata sandi sudah kami kirim. Cek kotak masuk dan folder spam.';

    private function isStaffEmail(string $email): bool
    {
        return User::where('email', $email)->first()?->isInternal() === true;
    }

    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request, Turnstile $turnstile): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.',
        ]);

        if (! $turnstile->passes($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withErrors(['email' => 'Verifikasi keamanan gagal. Muat ulang halaman lalu coba lagi.'])->onlyInput('email');
        }

        // Pengguna biasa tidak punya kata sandi (masuk lewat Google). Responsnya tetap sama supaya email terdaftar tidak bisa ditebak.
        if (app(GoogleOAuth::class)->membersGoogleOnly() && ! $this->isStaffEmail($data['email'])) {
            return back()->with('status', self::SENT);
        }

        try {
            Password::sendResetLink(['email' => $data['email']]);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', self::SENT);
    }

    public function form(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request, SessionManager $sessions): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRules::default()],
        ], ['email.required' => 'Email wajib diisi.', 'email.email' => 'Format email tidak valid.'] + PasswordRules::messages());

        if (app(GoogleOAuth::class)->membersGoogleOnly() && ! $this->isStaffEmail((string) $request->input('email'))) {
            return back()->withErrors(['email' => 'Link ini tidak valid atau sudah kedaluwarsa. Minta link baru.'])->onlyInput('email');
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($sessions) {
                // Memakai link dari email membuktikan pemilik email: tandai terverifikasi, cabut semua sesi lama.
                $user->forceFill([
                    'password' => $password,
                    'password_set_at' => now(),
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
                $sessions->purge($user);
                Audit::log('password.reset', $user, [], $user);
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Link ini tidak valid atau sudah kedaluwarsa. Minta link baru.'])->onlyInput('email');
        }

        return redirect()->route('login')->with('status', 'Kata sandi diperbarui. Silakan masuk dengan kata sandi barumu.');
    }
}
