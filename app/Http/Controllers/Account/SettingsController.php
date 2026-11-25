<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\ChatMessage;
use App\Models\CvProfile;
use App\Services\Account\DataExport;
use App\Services\Account\Preferences;
use App\Services\Account\SessionManager;
use App\Services\Audit\Audit;
use App\Services\Billing\EntitlementService;
use App\Services\Security\TwoFactor;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/** Pengaturan akun: profil, karier target, preferensi tiap menu, keamanan, serta data dan akun. */
class SettingsController extends Controller
{
    public function __construct(private Preferences $prefs, private SessionManager $sessions)
    {
    }

    public function index(Request $request, EntitlementService $entitlements)
    {
        \App\Models\UserDevice::where('user_id', $request->user()->id)->whereNotNull('alerted_at')->whereNull('acknowledged_at')->update(['acknowledged_at' => now()]);

        $user = $request->user();

        return view('account.settings', [
            'user' => $user,
            'groups' => $this->prefs->schema(),
            'values' => collect(array_keys($this->prefs->schema()))->mapWithKeys(fn ($g) => [$g => $this->prefs->group($user, $g)])->all(),
            'careers' => Career::where('is_active', true)->orderBy('rank')->pluck('name', 'id')->all(),
            'devices' => $this->sessions->list($user, $request->session()->getId()),
            'devicesSupported' => $this->sessions->supported(),
            'premium' => $entitlements->isPremium($user->id),
            'premiumUntil' => $entitlements->premiumUntil($user->id),
            'twoFactor' => ['enabled' => $user->hasTwoFactor(), 'pending' => $user->two_factor_secret !== null && ! $user->hasTwoFactor(),
                'secret' => $user->two_factor_secret !== null && ! $user->hasTwoFactor() ? $user->two_factor_secret : null,
                'uri' => $user->two_factor_secret !== null && ! $user->hasTwoFactor() ? app(TwoFactor::class)->uri($user) : null,
                'recoveryLeft' => app(TwoFactor::class)->recoveryLeft($user)],
            'chatCount' => ChatMessage::where('user_id', $user->id)->count(),
            'hasCv' => CvProfile::where('user_id', $user->id)->exists(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'headline' => ['nullable', 'string', 'max:120'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'headline.max' => 'Keterangan profil maksimal 120 karakter.',
        ]);
        $request->user()->forceFill([
            'name' => trim($data['name']),
            'headline' => filled($data['headline'] ?? null) ? trim($data['headline']) : null,
        ])->save();

        return $this->back('profil', 'Profil diperbarui.');
    }

    public function updateCareer(Request $request): RedirectResponse
    {
        $data = $request->validate(['career_id' => ['nullable', 'integer', 'exists:careers,id']], [
            'career_id.exists' => 'Karier yang dipilih tidak ditemukan.',
        ]);
        $request->user()->forceFill(['target_career_id' => $data['career_id'] ?? null])->save();

        return $this->back('karier', $data['career_id'] ?? null ? 'Karier target disimpan.' : 'Karier target dihapus.');
    }

    public function updatePreferences(Request $request, string $group): RedirectResponse
    {
        abort_unless($this->prefs->hasGroup($group), 404);

        if ($request->boolean('reset')) {
            $this->prefs->reset($request->user(), $group);

            return $this->back($group, 'Pengaturan '.mb_strtolower(config("preferences.{$group}.title")).' dikembalikan ke bawaan.');
        }

        $this->prefs->save($request->user(), $group, $request->validate($this->prefs->rules($group)));

        return $this->back($group, 'Pengaturan '.mb_strtolower(config("preferences.{$group}.title")).' disimpan.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        abort_if($request->user()->mustUseGoogle(), 403, 'Akunmu masuk dengan Google, jadi tidak memakai kata sandi.');

        // Akun Google yang belum punya kata sandi membuatnya tanpa "kata sandi saat ini" (yang lama acak, tidak ada yang tahu).
        $first = $request->user()->needsFirstPassword();
        $data = $request->validate(($first ? [] : ['current_password' => ['required', 'current_password']]) + [
            'password' => ['required', 'confirmed', PasswordRules::default()] + ($first ? [] : [3 => 'different:current_password']),
        ], [
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
        ] + PasswordRules::messages());
        $request->user()->forceFill(['password' => $data['password'], 'password_set_at' => now(), 'must_change_password' => false])->save();

        // Ganti sandi = perangkat lain ikut keluar, supaya sesi yang sudah dicuri tidak bertahan.
        $n = $this->sessions->revokeOthers($request->user(), $request->session()->getId());
        Audit::log('password.changed', $request->user(), ['sessions_revoked' => $n]);

        return $this->back('keamanan', $first ? 'Kata sandi dibuat. Sekarang kamu bisa masuk dengan email dan kata sandi, atau tetap dengan Google.' : ($n > 0 ? "Kata sandi diperbarui. {$n} perangkat lain dikeluarkan." : 'Kata sandi diperbarui.'));
    }

    public function logoutOthers(Request $request): RedirectResponse
    {
        // Akun Google tidak punya kata sandi untuk dikonfirmasi; sesi yang sedang masuk sudah cukup sebagai bukti.
        if (! $request->user()->mustUseGoogle()) {
            $request->validate(['current_password' => ['required', 'current_password']], [
                'current_password.current_password' => 'Kata sandi saat ini salah.',
            ]);
        }
        $n = $this->sessions->revokeOthers($request->user(), $request->session()->getId());

        return $this->back('keamanan', $this->sessions->supported()
            ? ($n > 0 ? "Keluar dari {$n} perangkat lain." : 'Tidak ada perangkat lain yang sedang masuk.')
            : 'Token "ingat saya" diganti. Daftar perangkat hanya tersedia bila sesi disimpan di database.');
    }

    public function export(Request $request, DataExport $export)
    {
        $name = 'data-'.config('platform.brand.name').'-'.now()->format('Ymd').'.json';

        return response()->streamDownload(
            fn () => print json_encode($export->build($request->user()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            strtolower($name),
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    public function clearChat(Request $request): RedirectResponse
    {
        ChatMessage::where('user_id', $request->user()->id)->delete();

        return $this->back('data', 'Riwayat chat Asisten AI dihapus.');
    }

    public function clearCv(Request $request): RedirectResponse
    {
        app(\App\Services\Careers\CvAnalyzer::class)->purge($request->user());

        return $this->back('data', 'Data CV dihapus.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isAdmin(), 403, 'Akun admin tidak bisa dihapus dari sini.');

        if ($user->mustUseGoogle()) {
            // Tanpa kata sandi: pengguna mengetik emailnya sendiri sebagai konfirmasi.
            $request->validate([
                'confirm_email' => ['required', 'string', fn ($a, $v, $fail) => strcasecmp(trim((string) $v), $user->email) === 0 || $fail('Email yang diketik tidak sama dengan emailmu.')],
                'confirm' => ['accepted'],
            ], [
                'confirm_email.required' => 'Ketik emailmu untuk mengonfirmasi.',
                'confirm.accepted' => 'Centang kotak konfirmasi untuk melanjutkan.',
            ]);
        } else {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'confirm' => ['accepted'],
            ], [
                'current_password.current_password' => 'Kata sandi saat ini salah.',
                'confirm.accepted' => 'Centang kotak konfirmasi untuk melanjutkan.',
            ]);
        }

        // Keluar dulu: logout menyimpan ulang token "ingat saya", dan menyimpan model yang sudah dihapus akan membuatnya lagi.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Tabel turunan terhapus lewat foreign key; pesanan dan hasil ujian tetap ada tanpa pemilik.
        DB::transaction(function () use ($user) {
            $this->sessions->purge($user);
            $user->delete();
        });

        return redirect()->route('home')->with('status', 'Akunmu sudah dihapus.');
    }

    private function back(string $section, string $message): RedirectResponse
    {
        return redirect()->to(route('account.settings').'#'.$section)->with('status', $message);
    }
}
