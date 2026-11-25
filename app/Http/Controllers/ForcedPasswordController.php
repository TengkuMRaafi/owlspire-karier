<?php

namespace App\Http\Controllers;

use App\Services\Account\SessionManager;
use App\Services\Audit\Audit;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Halaman wajib ganti sandi (diaktifkan admin, mis. setelah memberi kata sandi sementara). */
class ForcedPasswordController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.force-password');
    }

    public function update(Request $request, SessionManager $sessions): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRules::default(), 'different:current_password'],
        ], ['current_password.current_password' => 'Kata sandi sementara salah.'] + PasswordRules::messages());

        $user->forceFill(['password' => $data['password'], 'password_set_at' => now(), 'must_change_password' => false])->save();
        $sessions->revokeOthers($user, $request->session()->getId());
        Audit::log('password.changed', $user, ['via' => 'forced'], $user);

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard.index'))->with('status', 'Kata sandi diperbarui.');
    }
}
