<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Keluar dari mode "masuk sebagai pengguna" dan kembali ke akun admin yang asli. */
class ImpersonationController extends Controller
{
    public function stop(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonator_id');
        $admin = $adminId ? User::find($adminId) : null;
        if (! $admin || ! $admin->isAdmin()) {
            return redirect()->route('dashboard.index');
        }

        $target = $request->user();
        Auth::login($admin);
        $request->session()->regenerate();
        Audit::log('user.impersonated', $target, ['ended' => true], $admin);

        return $target
            ? redirect()->route('admin.users.show', $target)->with('status', 'Kamu kembali ke akun admin.')
            : redirect()->route('admin.users.index');
    }
}
