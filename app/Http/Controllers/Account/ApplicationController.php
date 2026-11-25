<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\JobAlert;
use App\Models\JobApplication;
use App\Models\TelegramLink;
use App\Services\Billing\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Pelacak lamaran (papan status) dan alert lowongan. */
class ApplicationController extends Controller
{
    public const STATUSES = [
        'wishlist' => 'Disimpan',
        'dilamar' => 'Sudah dilamar',
        'wawancara' => 'Wawancara',
        'tawaran' => 'Tawaran',
        'ditolak' => 'Ditolak',
    ];

    private const MAX_APPLICATIONS = 300;

    public function __construct(private EntitlementService $entitlements)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $apps = JobApplication::where('user_id', $user->id)->orderByDesc('updated_at')->get();

        return view('account.applications', [
            'statuses' => self::STATUSES,
            'columns' => $apps->groupBy('status'),
            'total' => $apps->count(),
            'followUps' => $apps->filter(fn ($a) => $a->follow_up_on && $a->follow_up_on->lte(today()->addDays(2)) && in_array($a->status, ['wishlist', 'dilamar', 'wawancara'], true))
                ->sortBy('follow_up_on')->values(),
            'alerts' => JobAlert::where('user_id', $user->id)->orderByDesc('id')->get(),
            'alertLimit' => $this->alertLimit($user->id),
            'telegramLinked' => TelegramLink::where('user_id', $user->id)->whereNotNull('chat_id')->exists(),
            'telegramOpen' => $this->telegramOpen($user->id),
        ]);
    }

    /** Dipakai halaman Lowongan (JSON) dan formulir manual. */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'company' => ['required', 'string', 'max:120'],
            'url' => ['nullable', 'url:http,https', 'max:1000'],
            'location' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(self::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'follow_up_on' => ['nullable', 'date'],
        ]);
        $userId = $request->user()->id;

        $existing = ! empty($data['url']) ? JobApplication::where('user_id', $userId)->where('url', $data['url'])->first() : null;
        if (! $existing && JobApplication::where('user_id', $userId)->count() >= self::MAX_APPLICATIONS) {
            $msg = 'Pelacakmu sudah penuh ('.self::MAX_APPLICATIONS.'). Hapus yang sudah tidak perlu.';

            return $request->expectsJson() ? response()->json(['error' => $msg], 422) : back()->withErrors(['title' => $msg])->withInput();
        }

        $status = $data['status'] ?? 'wishlist';
        $app = $existing ?? JobApplication::create($data + [
            'user_id' => $userId,
            'status' => $status,
            'applied_at' => $status === 'wishlist' ? null : now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $app->id, 'duplicate' => (bool) $existing]);
        }

        return redirect()->route('account.applications')->with('status', 'Lamaran ditambahkan.');
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        abort_unless((int) $application->user_id === (int) $request->user()->id, 404);

        $data = $request->validate([
            'status' => ['sometimes', 'in:'.implode(',', array_keys(self::STATUSES))],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'follow_up_on' => ['sometimes', 'nullable', 'date'],
        ]);

        if (isset($data['status']) && $data['status'] !== 'wishlist' && ! $application->applied_at) {
            $data['applied_at'] = now();
        }
        $application->update($data);

        return redirect()->route('account.applications')->with('status', 'Lamaran diperbarui.');
    }

    public function destroy(Request $request, JobApplication $application): RedirectResponse
    {
        abort_unless((int) $application->user_id === (int) $request->user()->id, 404);
        $application->delete();

        return redirect()->route('account.applications')->with('status', 'Lamaran dihapus.');
    }

    public function alertStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'keywords' => ['required', 'string', 'max:300'],
            'locations' => ['nullable', 'string', 'max:200'],
            'kind' => ['required', 'in:kerja,magang'],
            'channel' => ['required', 'in:email'],
        ]);

        $keywords = $this->split($data['keywords'], 5);
        if ($keywords === []) {
            return back()->withErrors(['keywords' => 'Isi minimal satu kata kunci.'])->withInput();
        }
        if ($data['channel'] === 'telegram' && ! TelegramLink::where('user_id', $user->id)->whereNotNull('chat_id')->exists()) {
            $msg = $this->telegramOpen($user->id) ? 'Hubungkan Telegram dulu di halaman Rencana belajar.' : 'Alert lewat Telegram tersedia untuk pengguna Premium.';

            return back()->withErrors(['channel' => $msg])->withInput();
        }
        if (JobAlert::where('user_id', $user->id)->count() >= $this->alertLimit($user->id)) {
            return back()->withErrors(['keywords' => 'Batas alert tercapai. Hapus satu atau naik ke Premium.'])->withInput();
        }

        JobAlert::create([
            'user_id' => $user->id,
            'keywords' => $keywords,
            'locations' => $this->split((string) ($data['locations'] ?? ''), 3),
            'kind' => $data['kind'],
            'channel' => $data['channel'],
            'is_active' => true,
        ]);

        return redirect()->route('account.applications')->with('status', 'Alert dibuat. Ringkasannya dikirim tiap pagi bila ada lowongan baru yang cocok.');
    }

    public function alertToggle(Request $request, JobAlert $alert): RedirectResponse
    {
        abort_unless((int) $alert->user_id === (int) $request->user()->id, 404);
        $alert->update(['is_active' => ! $alert->is_active]);

        return redirect()->route('account.applications');
    }

    public function alertDestroy(Request $request, JobAlert $alert): RedirectResponse
    {
        abort_unless((int) $alert->user_id === (int) $request->user()->id, 404);
        $alert->delete();

        return redirect()->route('account.applications')->with('status', 'Alert dihapus.');
    }

    /** Menautkan Telegram dilakukan di halaman Rencana belajar; bila halaman itu khusus Premium, pengguna Gratis tidak bisa menautkan. */
    private function telegramOpen(int $userId): bool
    {
        return ! config('platform.premium_only.plan') || $this->entitlements->isPremium($userId);
    }

    private function alertLimit(int $userId): int
    {
        return $this->entitlements->isPremium($userId) ? 10 : 2;
    }

    /** @return string[] */
    private function split(string $text, int $max): array
    {
        return array_slice(array_values(array_unique(array_filter(array_map(fn ($x) => mb_substr(trim($x), 0, 60), explode(',', $text))))), 0, $max);
    }
}
