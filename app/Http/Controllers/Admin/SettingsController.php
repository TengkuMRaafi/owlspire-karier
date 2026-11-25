<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\Account\SessionManager;
use App\Services\Audit\Audit;
use App\Support\PasswordRules;

/** Pengaturan panel: akun sendiri untuk admin dan staf; peran, pembayaran, platform, dan sistem hanya untuk admin. */
class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $system = $user->canAccess('system');
        $data = ['user' => $user, 'system' => $system];

        if ($system) {
            $data += [
                'roleCounts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
                'team' => User::whereIn('role', config('roles.internal'))->orderByRaw("role = 'admin' desc")->orderBy('name')->get(['id', 'name', 'email', 'role']),
                'plans' => Plan::orderBy('price')->get(),
                'waiting' => Order::where('status', 'pending')->whereNotNull('meta->claimed_at')->count(),
                // Kunci rahasia tidak pernah ditampilkan; hanya status terisi atau belum.
                'filled' => fn (?string $v) => filled($v),
            ];
        }

        return view('admin.settings', $data);
    }

    public function updatePassword(Request $request, SessionManager $sessions): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRules::default(), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
        ] + PasswordRules::messages());

        $request->user()->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        $n = $sessions->revokeOthers($request->user(), $request->session()->getId());
        Audit::log('password.changed', $request->user(), ['sessions_revoked' => $n]);

        return redirect()->to(route('admin.settings').'#akun')->with('status', 'Kata sandi diperbarui.');
    }
}
