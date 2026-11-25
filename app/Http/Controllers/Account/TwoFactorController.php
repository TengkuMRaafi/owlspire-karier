<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Audit\Audit;
use App\Services\Security\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Verifikasi 2 langkah (TOTP) untuk akun sendiri. Semua aksi di sini diblokir saat admin masuk sebagai pengguna. */
class TwoFactorController extends Controller
{
    public function __construct(private TwoFactor $twoFactor)
    {
    }

    public function begin(Request $request): RedirectResponse
    {
        abort_if($request->user()->mustUseGoogle(), 403, 'Akunmu masuk dengan Google; verifikasi 2 langkah diatur di akun Google-mu.');
        abort_if($request->user()->hasTwoFactor(), 403);
        $this->twoFactor->begin($request->user());

        return $this->back('Scan atau salin kunci di bawah ke aplikasi autentikator, lalu masukkan kode 6 digitnya.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        abort_if($request->user()->mustUseGoogle(), 403);
        $request->validate(['code' => ['required', 'string', 'max:12']], ['code.required' => 'Masukkan kode 6 digit dari aplikasi autentikator.']);

        $codes = $this->twoFactor->confirm($request->user(), (string) $request->input('code'));
        if ($codes === null) {
            return $this->back(null, 'Kode salah atau sudah kedaluwarsa. Coba kode terbaru dari aplikasimu.');
        }
        Audit::log('twofactor.enabled', $request->user());

        return $this->back('Verifikasi 2 langkah aktif. Simpan kode pemulihan di bawah sekarang, kode ini hanya tampil sekali.')->with('recovery_codes', $codes);
    }

    public function recovery(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasTwoFactor(), 403);
        abort_if($request->user()->mustUseGoogle(), 403);
        $this->requirePassword($request);

        return $this->back('Kode pemulihan baru dibuat. Kode lama sudah tidak berlaku.')->with('recovery_codes', $this->twoFactor->regenerateRecovery($request->user()));
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->requirePassword($request);
        $this->twoFactor->disable($request->user());
        Audit::log('twofactor.disabled', $request->user());

        return $this->back('Verifikasi 2 langkah dimatikan.');
    }

    private function requirePassword(Request $request): void
    {
        // Akun Google tidak punya kata sandi; mematikan 2 langkah lama mereka tidak meminta sandi.
        if ($request->user()->mustUseGoogle()) {
            return;
        }

        $request->validate(['current_password' => ['required', 'current_password']], ['current_password.current_password' => 'Kata sandi saat ini salah.']);
    }

    private function back(?string $status, ?string $error = null): RedirectResponse
    {
        $r = redirect()->to(route('account.settings').'#keamanan');

        return $error ? $r->with('error', $error) : $r->with('status', $status);
    }
}
