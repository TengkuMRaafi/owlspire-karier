<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Services\Account\DeviceGuard;
use App\Services\Audit\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait CompletesLogin
{
    /** Langkah terakhir masuk (setelah kata sandi, dan kode 2 langkah bila ada). */
    protected function completeLogin(Request $request, User $user, bool $remember): RedirectResponse
    {
        // Batasi perangkat bersamaan dan catat perangkat baru (sebelum login, agar token "ingat saya" yang baru terpakai).
        app(DeviceGuard::class)->beforeLogin($request, $user);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();

        // Hanya akun admin dan staf yang dicatat tiap masuk; untuk pengguna biasa cukup kolom login terakhir.
        if ($user->isInternal()) {
            Audit::log('login.success', $user, [], $user);
        }

        $home = $user->isAdmin() ? route('admin.dashboard') : route('dashboard.index');

        return redirect()->intended($home);
    }
}
