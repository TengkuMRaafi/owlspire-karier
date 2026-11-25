<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    /** Untuk pengguna yang sudah masuk tapi emailnya belum terverifikasi (mis. akun lama). */
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.verify-email');
    }

    /** Halaman "Cek emailmu" setelah daftar, atau saat masuk dengan email yang belum terverifikasi. */
    public function sent(Request $request)
    {
        $email = $request->session()->get('verify_email');
        if (! is_string($email) || $email === '') {
            return redirect()->route('login');
        }
        $request->session()->keep(['status', 'error']);

        return view('auth.verify-sent', ['email' => $email]);
    }

    /** Link dari email. Tidak butuh sesi masuk (link bertanda tangan, dan hash cocok dengan email akun). */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::find($id);
        abort_unless($user && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if (Auth::check() && (int) Auth::id() === (int) $user->id) {
            return redirect()->route('dashboard.index')->with('status', 'Email terverifikasi. Terima kasih!');
        }

        return redirect()->route('login')->with('status', 'Email terverifikasi. Silakan masuk.');
    }

    /** Kirim ulang untuk pengguna yang belum masuk. Respons sama untuk semua email supaya email terdaftar tidak bisa ditebak. */
    public function resend(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = Str::lower(trim($data['email']));
        $key = 'verify-resend:'.sha1($email);

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 600);
            $user = User::whereRaw('lower(email) = ?', [$email])->first();
            if ($user && ! $user->isInternal() && ! $user->hasVerifiedEmail()) {
                try {
                    $user->sendEmailVerificationNotification();
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('verification.sent')
            ->with('verify_email', $email)
            ->with('status', 'Kalau emailnya terdaftar dan belum terverifikasi, link baru sudah dikirim. Cek juga folder spam.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back();
        }
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Link verifikasi baru sudah dikirim ke '.$request->user()->email.'.');
    }
}
