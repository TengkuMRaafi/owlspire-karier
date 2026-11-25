<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsage;
use App\Models\AuditLog;
use App\Models\ChatUsage;
use App\Models\CvAnalysis;
use App\Models\CvProfile;
use App\Models\Entitlement;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\User;
use App\Services\Account\SessionManager;
use App\Services\Audit\Audit;
use App\Services\Billing\EntitlementService;
use App\Services\Reports\UserXlsx;
use App\Services\Security\TwoFactor;
use App\Support\PasswordRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Kelola akun. Seluruh controller ini hanya terbuka untuk admin (area `users` di config/roles.php). */
class UserController extends Controller
{
    private const PER_PAGE = [10, 25, 50, 100];

    public function __construct(private EntitlementService $entitlements, private SessionManager $sessions)
    {
    }

    /** Query daftar pengguna dengan semua filter; dipakai bersama oleh daftar dan ekspor CSV. */
    private function filtered(Request $request): Builder
    {
        $query = User::query();

        if ($search = trim((string) $request->query('cari'))) {
            $query->where(fn ($q) => $q->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }
        if (in_array($role = $request->query('role'), array_keys(config('roles.roles')), true)) {
            $query->where('role', $role);
        }

        // Pembeda paket: Premium = punya langganan aktif (belum kedaluwarsa). Hanya berlaku untuk pengguna biasa.
        $activeSub = fn () => Entitlement::where('type', 'subscription')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        $paket = $request->query('paket');
        if ($paket === 'premium') {
            $query->where('role', 'member')->whereIn('id', $activeSub()->select('user_id'));
        } elseif ($paket === 'gratis') {
            $query->where('role', 'member')->whereNotIn('id', $activeSub()->select('user_id'));
        }

        match ($request->query('status')) {
            'aktif' => $query->whereNull('suspended_at'),
            'ditangguhkan' => $query->whereNotNull('suspended_at'),
            default => null,
        };
        match ($request->query('verifikasi')) {
            'ya' => $query->whereNotNull('email_verified_at'),
            'belum' => $query->whereNull('email_verified_at'),
            default => null,
        };
        if ($request->query('dua_langkah') === 'ya') {
            $query->whereNotNull('two_factor_confirmed_at');
        }

        match ($request->query('urut')) {
            'login' => $query->orderByRaw('last_login_at is null')->orderByDesc('last_login_at'),
            'nama' => $query->orderBy('name'),
            'lama' => $query->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        return $query;
    }

    public function index(Request $request)
    {
        $perPage = in_array((int) $request->query('per'), self::PER_PAGE, true) ? (int) $request->query('per') : 10;
        $users = $this->filtered($request)->paginate($perPage)->withQueryString();
        $ids = $users->pluck('id');

        $activeSub = fn () => Entitlement::where('type', 'subscription')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));

        return view('admin.users.index', [
            'users' => $users,
            'perPage' => $perPage,
            'perOptions' => array_combine(self::PER_PAGE, array_map(fn ($n) => "{$n} per halaman", self::PER_PAGE)),
            'tickets' => Entitlement::where('type', 'exam_pass')->where('remaining', '>', 0)->whereIn('user_id', $ids)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->selectRaw('user_id, count(distinct reference_id) as total')->groupBy('user_id')->pluck('total', 'user_id'),
            'attempts' => ExamAttempt::whereIn('user_id', $ids)->whereNotNull('finished_at')->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id'),
            'premium' => $activeSub()->whereIn('user_id', $ids)->get(['user_id', 'expires_at'])->groupBy('user_id')
                ->map(fn ($rows) => $rows->contains(fn ($r) => $r->expires_at === null) ? null : $rows->max('expires_at')),
        ]);
    }

    /** Unduh daftar (sesuai filter) sebagai Excel berformat. Bila ekstensi zip PHP belum aktif, jatuh ke CSV supaya unduhan tetap jalan. */
    public function export(Request $request): Response
    {
        Audit::log('user.exported', null, ['filters' => $request->only(['cari', 'role', 'paket', 'status', 'verifikasi'])]);
        $query = $this->filtered($request);

        if (! class_exists(\ZipArchive::class)) {
            return $this->exportCsv($query);
        }

        return response()->download(app(UserXlsx::class)->build($query, $this->filterLabel($request)), 'pengguna-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Keterangan saringan untuk baris judul Excel, mis. "paket premium, status aktif". */
    private function filterLabel(Request $request): string
    {
        $parts = [];
        if ($q = trim((string) $request->query('cari'))) {
            $parts[] = 'pencarian "'.mb_substr($q, 0, 40).'"';
        }
        if (($role = $request->query('role')) && isset(config('roles.roles')[$role])) {
            $parts[] = 'role '.mb_strtolower(config('roles.roles')[$role]['label']);
        }
        if (in_array($paket = $request->query('paket'), ['premium', 'gratis'], true)) {
            $parts[] = 'paket '.$paket;
        }
        if (in_array($status = $request->query('status'), ['aktif', 'ditangguhkan'], true)) {
            $parts[] = 'status '.$status;
        }
        if (in_array($v = $request->query('verifikasi'), ['ya', 'belum'], true)) {
            $parts[] = $v === 'ya' ? 'email terverifikasi' : 'email belum terverifikasi';
        }
        if ($request->query('dua_langkah') === 'ya') {
            $parts[] = 'verifikasi 2 langkah aktif';
        }

        return implode(', ', $parts);
    }

    /** Cadangan bila Excel tidak bisa dibuat. Sel yang diawali = + - @ diberi tanda kutip agar aman dibuka di Excel. */
    private function exportCsv(Builder $query): StreamedResponse
    {
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($query, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['ID', 'Nama', 'Email', 'Role', 'Status', 'Email terverifikasi', '2 langkah', 'Terdaftar', 'Login terakhir', 'Ujian selesai', 'Premium sampai']);
            $query->reorder('id')->chunkById(500, function ($chunk) use ($out, $safe) {
                $ids = $chunk->pluck('id');
                $done = ExamAttempt::whereIn('user_id', $ids)->whereNotNull('finished_at')->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
                $subs = Entitlement::where('type', 'subscription')->whereIn('user_id', $ids)
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->get()->groupBy('user_id');
                foreach ($chunk as $u) {
                    $sub = $subs[$u->id] ?? null;
                    fputcsv($out, [
                        $u->id, $safe($u->name), $safe($u->email), $u->roleLabel(), $u->isSuspended() ? 'Ditangguhkan' : 'Aktif',
                        $u->email_verified_at?->format('Y-m-d') ?? 'belum', $u->hasTwoFactor() ? 'ya' : 'tidak',
                        $u->created_at?->format('Y-m-d H:i'), $u->last_login_at?->format('Y-m-d H:i'), $done[$u->id] ?? 0,
                        $u->isInternal() ? '-' : ($sub ? ($sub->contains(fn ($r) => $r->expires_at === null) ? 'tanpa batas' : $sub->max('expires_at')->format('Y-m-d')) : ''),
                    ]);
                }
            });
            fclose($out);
        }, 'pengguna-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Halaman tambah akun (terpisah dari daftar). */
    public function create()
    {
        return view('admin.users.form', ['user' => new User(['role' => 'member']), 'roles' => $this->roleOptions()]);
    }

    /** Admin membuat akun pengguna atau staf. Akun admin tidak bisa dibuat dari sini. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', PasswordRules::default()],
            'role' => ['sometimes', Rule::in(config('roles.assignable'))],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Ubah akunnya lewat daftar pengguna.',
        ] + PasswordRules::messages());

        if (($data['role'] ?? 'staff') === 'member' && app(\App\Services\Auth\GoogleOAuth::class)->membersGoogleOnly()) {
            return back()->withInput()->withErrors(['role' => 'Pengguna biasa mendaftar sendiri lewat Google. Dari sini hanya akun staf yang bisa dibuat.']);
        }

        $user = new User();
        $user->forceFill([
            'name' => trim($data['name']), 'email' => strtolower(trim($data['email'])), 'role' => $data['role'] ?? 'staff',
            'email_verified_at' => now(), 'must_change_password' => $request->boolean('force_change', true),
        ]);
        $user->password = $data['password']; // di-hash otomatis oleh cast pada model
        $user->save();
        Audit::log('user.created', $user, ['role' => $user->role]);

        return redirect()->route('admin.users.index')->with('status', "Akun {$user->roleLabel()} {$user->name} dibuat. Berikan kata sandi sementaranya secara langsung"
            .($user->must_change_password ? '; dia akan diminta menggantinya saat masuk pertama.' : '.'));
    }

    public function show(User $user)
    {
        $premium = ! $user->isInternal() && $this->entitlements->isPremium($user->id);
        $today = now()->toDateString();
        $lastCv = CvAnalysis::where('user_id', $user->id)->latest('id')->first();

        return view('admin.users.show', [
            'user' => $user,
            'premium' => $premium,
            'premiumUntil' => $premium ? $this->entitlements->premiumUntil($user->id) : null,
            'tickets' => (int) Entitlement::where('user_id', $user->id)->where('type', 'exam_pass')
                ->where('remaining', '>', 0)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->distinct()->count('reference_id'),
            'attempts' => ExamAttempt::with('type')->where('user_id', $user->id)->whereNotNull('finished_at')->latest('finished_at')->limit(5)->get(),
            'attemptCount' => ExamAttempt::where('user_id', $user->id)->whereNotNull('finished_at')->count(),
            'orderCount' => Order::where('user_id', $user->id)->count(),
            'orders' => Order::where('user_id', $user->id)->latest('id')->limit(5)->get(),
            'types' => ExamType::orderBy('position')->get(),
            'usage' => [
                'chat' => (int) ChatUsage::where('user_id', $user->id)->where('day', $today)->value('count'),
                'ai' => AiUsage::where('user_id', $user->id)->where('day', $today)->pluck('used', 'feature')->all(),
                'hasCv' => CvProfile::where('user_id', $user->id)->exists(),
                'cvScore' => $lastCv?->score,
                'cvAt' => $lastCv?->created_at,
            ],
            'logs' => AuditLog::where(fn ($q) => $q->where('subject_type', 'user')->where('subject_id', $user->id)->orWhere('actor_id', $user->id))
                ->latest('id')->limit(8)->get(),
            'twoFactor' => $user->hasTwoFactor(),
            'devices' => $this->sessions->supported() ? $this->sessions->list($user, '') : null,
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user, 'roles' => $this->roleOptions()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $locked = $user->isAdmin() || $user->id === $request->user()->id; // role admin dan akunmu sendiri tidak diubah di sini

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', PasswordRules::default()],
            'role' => ['sometimes', Rule::in(config('roles.assignable'))],
        ], ['email.unique' => 'Email ini sudah dipakai akun lain.'] + PasswordRules::messages());

        $changes = [];
        $newEmail = strtolower(trim($data['email']));
        if ($newEmail !== strtolower($user->email)) {
            $changes[] = 'email';
            $user->forceFill(['email_verified_at' => null]); // email baru harus dibuktikan lagi
        }
        $user->forceFill(['name' => trim($data['name']), 'email' => $newEmail]);
        if (! $locked && isset($data['role']) && $data['role'] !== $user->role) {
            $changes[] = "role {$user->role} -> {$data['role']}";
            $user->forceFill(['role' => $data['role']]);
        }
        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->must_change_password = $request->boolean('force_change', true) && $user->id !== $request->user()->id;
            $changes[] = 'kata sandi';
            $this->sessions->purge($user); // sandi diganti admin: sesi lama pengguna berakhir
        }
        $user->save();
        Audit::log('user.updated', $user, ['changed' => $changes]);

        return redirect()->route('admin.users.index')->with('status', "Akun {$user->name} diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Akun admin tidak bisa dihapus.');
        }
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        }

        $name = $user->name;
        Audit::log('user.deleted', $user, ['name' => $name, 'role' => $user->role]);
        $this->sessions->purge($user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', "Akun {$name} dihapus.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        // Admin tidak masuk daftar yang boleh diberikan: role ini hanya dibuat lewat `php artisan owlspire:admin`.
        $data = $request->validate(['role' => ['required', 'in:'.implode(',', config('roles.assignable'))]]);

        if ($user->isAdmin()) {
            return back()->with('error', 'Role admin terkunci dan tidak bisa diubah.');
        }
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Kamu tidak bisa mengubah role akunmu sendiri.');
        }

        $from = $user->role;
        $user->forceFill(['role' => $data['role']])->save();
        Audit::log('user.role_changed', $user, ['from' => $from, 'to' => $data['role']]);

        return back()->with('status', "Role {$user->name} diubah menjadi ".config("roles.roles.{$data['role']}.label").'.');
    }

    public function grantTicket(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $this->entitlements->grantExamPass($user->id, (int) $data['exam_type_id'], (int) $data['quantity']);
        Audit::log('user.ticket_granted', $user, ['exam_type_id' => (int) $data['exam_type_id'], 'quantity' => (int) $data['quantity']]);

        return back()->with('status', "Tiket simulasi diberikan kepada {$user->name}.");
    }

    /** Beri, perpanjang, atau cabut Premium secara manual. Hanya untuk pengguna biasa. */
    public function premium(Request $request, User $user): RedirectResponse
    {
        if ($user->isInternal()) {
            return back()->with('error', 'Admin dan staf sudah punya akses penuh tanpa paket.');
        }
        $data = $request->validate([
            'mode' => ['required', 'in:grant,revoke'],
            'duration' => ['required_if:mode,grant', 'nullable', 'in:30,90,180,365,lifetime'],
        ]);

        if ($data['mode'] === 'revoke') {
            $n = $this->entitlements->revokePremium($user->id);
            Audit::log('user.premium_revoked', $user, ['entitlements' => $n]);

            return back()->with('status', $n > 0 ? "Premium {$user->name} dihentikan." : 'Pengguna ini memang tidak sedang Premium.');
        }

        $days = $data['duration'] === 'lifetime' ? null : (int) $data['duration'];
        $this->entitlements->grantPremium($user->id, $days);
        Audit::log('user.premium_granted', $user, ['days' => $days ?? 'tanpa batas']);

        return back()->with('status', 'Premium '.($days ? "ditambah {$days} hari" : 'diberikan tanpa batas waktu')." untuk {$user->name}.");
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin() || $user->id === $request->user()->id) {
            return back()->with('error', 'Akun admin dan akunmu sendiri tidak bisa ditangguhkan.');
        }
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $user->forceFill(['suspended_at' => now(), 'suspended_reason' => $data['reason'] ?? null, 'remember_token' => null])->save();
        $this->sessions->purge($user);
        Audit::log('user.suspended', $user, ['reason' => $data['reason'] ?? null]);

        return back()->with('status', "Akun {$user->name} ditangguhkan dan dikeluarkan dari semua perangkat.");
    }

    public function activate(User $user): RedirectResponse
    {
        $user->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();
        Audit::log('user.activated', $user);

        return back()->with('status', "Penangguhan {$user->name} dicabut.");
    }

    public function sendReset(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);
        Audit::log('user.reset_link_sent', $user, ['ok' => $status === Password::RESET_LINK_SENT]);

        return back()->with($status === Password::RESET_LINK_SENT ? 'status' : 'error',
            $status === Password::RESET_LINK_SENT ? "Link atur ulang sandi dikirim ke {$user->email}." : 'Link belum bisa dikirim sekarang. Coba lagi sebentar lagi.');
    }

    public function forcePassword(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Akun admin mengganti sandinya sendiri.');
        }
        $on = ! $user->must_change_password;
        $user->forceFill(['must_change_password' => $on])->save();
        Audit::log('user.force_password_change', $user, ['on' => $on]);

        return back()->with('status', $on ? "{$user->name} wajib mengganti sandi saat membuka akunnya berikutnya." : 'Kewajiban ganti sandi dicabut.');
    }

    /** Mengosongkan pemakaian harian (Asisten AI, Wawancara AI, Analisis CV, dll) agar pengguna bisa memakai jatahnya lagi tanpa menunggu pergantian hari. */
    public function resetUsage(User $user): RedirectResponse
    {
        $today = now()->toDateString();
        $chat = ChatUsage::where('user_id', $user->id)->where('day', $today)->delete();
        $ai = AiUsage::where('user_id', $user->id)->where('day', $today)->delete();
        Audit::log('user.reset_usage', $user, ['chat_rows' => $chat, 'ai_rows' => $ai]);

        return back()->with('status', "Pemakaian harian {$user->name} direset. Jatah Asisten AI, Wawancara AI, dan Analisis CV terisi penuh lagi.");
    }

    public function verifyEmail(User $user): RedirectResponse
    {
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        Audit::log('user.email_verified', $user);

        return back()->with('status', 'Email ditandai terverifikasi.');
    }

    public function disableTwoFactor(Request $request, User $user, TwoFactor $twoFactor): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Matikan verifikasi 2 langkahmu sendiri lewat Pengaturan.');
        }
        $twoFactor->disable($user);
        Audit::log('user.twofactor_disabled', $user);

        return back()->with('status', 'Verifikasi 2 langkah dimatikan. Pengguna bisa mengaktifkannya lagi dari Pengaturan.');
    }

    public function logoutAll(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Untuk keluar dari perangkat lain, pakai Pengaturan akunmu.');
        }
        $n = $this->sessions->revokeOthers($user, '');
        Audit::log('user.sessions_revoked', $user, ['sessions' => $n]);

        return back()->with('status', $this->sessions->supported() ? "{$n} sesi dikeluarkan." : 'Token "ingat saya" diganti. Daftar sesi hanya tersedia bila SESSION_DRIVER=database.');
    }

    /** Masuk sebagai pengguna biasa untuk membantu menelusuri keluhan. Dicatat di Log aktivitas dan bisa diakhiri kapan saja. */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        if ($user->isInternal() || $user->id === $request->user()->id) {
            return back()->with('error', 'Hanya akun pengguna biasa yang bisa dibuka lewat "masuk sebagai".');
        }
        if ($user->isSuspended()) {
            return back()->with('error', 'Akun ini sedang ditangguhkan. Cabut penangguhan dulu bila perlu membukanya.');
        }

        $adminId = $request->user()->id;
        Audit::log('user.impersonated', $user, ['started' => true]);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $adminId);

        return redirect()->route('dashboard.index')->with('status', "Kamu masuk sebagai {$user->name}. Pakai tombol di atas halaman untuk kembali.");
    }

    /** Aksi massal dari daftar: tangguhkan, aktifkan, tandai terverifikasi, atau wajib ganti sandi. Admin dan akunmu dilewati. */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:suspend,activate,verify,force_password'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'Pilih minimal satu pengguna.', 'ids.min' => 'Pilih minimal satu pengguna.']);

        $users = User::whereIn('id', $data['ids'])->where('role', '!=', 'admin')->where('id', '!=', $request->user()->id)->get();
        foreach ($users as $u) {
            switch ($data['action']) {
                case 'suspend':
                    $u->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Aksi massal admin', 'remember_token' => null])->save();
                    $this->sessions->purge($u);
                    break;
                case 'activate':
                    $u->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();
                    break;
                case 'verify':
                    $u->forceFill(['email_verified_at' => $u->email_verified_at ?? now()])->save();
                    break;
                case 'force_password':
                    $u->forceFill(['must_change_password' => true])->save();
                    break;
            }
        }
        Audit::log('user.bulk', null, ['action' => $data['action'], 'count' => $users->count(), 'ids' => $users->pluck('id')->all()]);

        return back()->with('status', "Aksi diterapkan ke {$users->count()} akun".($users->count() < count($data['ids']) ? ' (admin dan akunmu dilewati).' : '.'));
    }

    /** Role yang boleh dipilih di formulir: pengguna dan staf (admin tidak). */
    private function roleOptions(): array
    {
        return collect(config('roles.roles'))->only(config('roles.assignable'))->map(fn ($r) => $r['label'])->all();
    }
}
