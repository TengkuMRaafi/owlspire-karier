<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CompletesLogin;
use App\Models\User;
use App\Services\Audit\Audit;
use App\Services\Security\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Langkah kedua masuk: kode dari aplikasi autentikator atau kode pemulihan. */
class TwoFactorChallengeController extends Controller
{
    use CompletesLogin;

    public function show(Request $request)
    {
        if (! $this->pending($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $pending = $this->pending($request);
        if (! $pending) {
            return redirect()->route('login')->with('error', 'Sesi masuk berakhir. Masuk lagi dari awal.');
        }

        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Masukkan kode dari aplikasi autentikator atau kode pemulihan.']);

        $user = User::find($pending['id']);
        if (! $user || ! $user->hasTwoFactor() || $user->isSuspended()) {
            $request->session()->forget('login.2fa');

            return redirect()->route('login');
        }

        $key = 'login-2fa:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Coba lagi sebentar lagi.']);
        }

        if (! $twoFactor->verifyLogin($user, (string) $request->input('code'))) {
            RateLimiter::hit($key, 300);
            Audit::log('login.2fa_failed', $user, [], $user);

            return back()->withErrors(['code' => 'Kode salah atau sudah dipakai. Coba kode terbaru.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget('login.2fa');

        return $this->completeLogin($request, $user, (bool) $pending['remember']);
    }

    /** @return array{id:int,remember:bool}|null */
    private function pending(Request $request): ?array
    {
        $p = $request->session()->get('login.2fa');

        return is_array($p) && ($p['expires'] ?? 0) > now()->timestamp ? $p : null;
    }
}
