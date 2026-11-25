<?php

namespace App\View\Composers;

use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Account\DeviceGuard;
use App\Services\Billing\EntitlementService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Menyiapkan data sidebar panel (menu, penanda aktif, kunci Premium, akun) untuk layout admin dan akun. */
class PanelComposer
{
    public function __construct(private EntitlementService $entitlements)
    {
    }

    public function compose(View $view): void
    {
        $area = $view->getData()['area'] ?? 'account';
        $config = config("panel.{$area}");
        $user = auth()->user();
        $isAdminArea = $area === 'admin';
        $internal = (bool) $user?->isInternal();
        $premium = ! $isAdminArea && $user && $this->entitlements->isPremium($user->id);

        $groups = collect($config['groups'])->map(fn (array $group) => [
            'label' => $group['label'],
            'items' => collect($group['items'])
                ->filter(fn (array $item) => Route::has($item['route'])
                    && (empty($item['area']) || $user?->canAccess($item['area'])) // menu admin sesuai peran
                    && ! ($internal && ! empty($item['plan']))) // admin dan staf tidak punya paket
                ->map(fn (array $item) => [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => route($item['route']),
                    'active' => request()->routeIs(...($item['match'] ?? [$item['route']])),
                    'locked' => ! $isAdminArea && ! empty($item['premium']) && ! $premium,
                    'count' => ($item['badge'] ?? null) === 'orders' ? $this->waitingOrders() : 0,
                ])->values()->all(),
        ])->filter(fn (array $group) => $group['items'])->values()->all();

        $view->with([
            'nav' => $groups,
            'navHome' => route($config['home']),
            'navBadge' => $isAdminArea && $user?->isStaff() ? 'Staf' : $config['badge'],
            'isAdminArea' => $isAdminArea,
            'isPremium' => $premium,
            'isInternal' => $internal,
            'adminUrl' => $internal && Route::has('admin.dashboard') ? route('admin.dashboard') : null,
            'premiumUntil' => $premium ? $this->entitlements->premiumUntil($user->id) : null,
            'meName' => $user?->name ?? '',
            'meInitial' => Str::upper(Str::substr($user?->name ?? '?', 0, 1)),
            'meCaption' => $internal ? ($user->isAdmin() ? 'Administrator' : 'Staf') : ($premium ? 'Paket Premium' : 'Paket Gratis'),
            'meEmail' => $user?->email ?? '',
            'meJoined' => $user?->created_at?->translatedFormat('M Y'),
            'profileUrl' => Route::has($isAdminArea ? 'admin.settings' : 'account.settings') ? route($isAdminArea ? 'admin.settings' : 'account.settings') : null,
            'siteUrl' => route('home'),
            'notices' => $user ? $this->notices($user, $isAdminArea) : [],
            'history' => $user ? $this->history($user, $isAdminArea) : [],
            'historyUrl' => $isAdminArea
                ? (Route::has('admin.attempts.index') && $user?->canAccess('attempts') ? route('admin.attempts.index') : null)
                : (Route::has('account.history') ? route('account.history') : null),
            'upgradeUrl' => Route::has('account.subscription') ? route('account.subscription') : null,
        ]);
    }

    /** Pemberitahuan di lonceng topbar. Tiap item: icon, text, url. Kosong bila tidak ada yang perlu perhatian. */
    private function notices(User $user, bool $isAdminArea): array
    {
        $userId = $user->id;
        $items = [];

        if ($isAdminArea) {
            if ($user->canAccess('orders') && ($n = $this->waitingOrders()) > 0 && Route::has('admin.orders.index')) {
                $items[] = ['icon' => 'cart', 'text' => "{$n} pesanan menunggu dicek", 'url' => route('admin.orders.index')];
            }
            $videos = Lesson::where('is_approved', false)->where('provider', 'youtube')->count();
            if ($user->canAccess('learning') && $videos > 0 && Route::has('admin.learning.index')) {
                $items[] = ['icon' => 'play', 'text' => "{$videos} video menunggu persetujuan", 'url' => route('admin.learning.index')];
            }

            return $items;
        }

        // Tiap jenis pemberitahuan bisa dimatikan di Pengaturan > Notifikasi.
        $running = $user->pref('notifications.exam_running') ? ExamAttempt::where('user_id', $userId)->whereNull('finished_at')->where('deadline_at', '>', now())
            ->with('type')->latest('id')->first() : null;
        if ($running && Route::has('exam.room')) {
            $items[] = ['icon' => 'clock', 'text' => 'Ujian '.($running->type->name ?? '').' belum selesai, sisa '.(int) ceil($running->remainingSeconds() / 60).' menit', 'url' => route('exam.room', $running)];
        }
        $order = $user->pref('notifications.billing') ? Order::where('user_id', $userId)->where('status', 'pending')->latest('id')->first() : null;
        if ($order && Route::has('billing.invoice')) {
            $items[] = ['icon' => 'card', 'text' => 'Ada tagihan yang belum dibayar', 'url' => route('billing.invoice', $order)];
        }

        $device = $user->pref('notifications.new_device') ? UserDevice::where('user_id', $userId)->whereNotNull('alerted_at')->whereNull('acknowledged_at')
            ->where('alerted_at', '>=', now()->subDays(3))->where('token_hash', '!=', DeviceGuard::currentHash(request()))->latest('alerted_at')->first() : null;
        if ($device && Route::has('account.settings')) {
            $items[] = ['icon' => 'shield', 'text' => 'Akunmu dipakai masuk dari '.$device->label.'. Bukan kamu? Periksa keamanan', 'url' => route('account.settings').'#keamanan'];
        }

        $until = $user->pref('notifications.premium_end') ? $this->entitlements->premiumUntil($userId) : null;
        if ($until && $until->isBefore(now()->addDays(7)) && Route::has('account.subscription')) {
            $days = max(1, (int) ceil(now()->diffInHours($until) / 24));
            $items[] = ['icon' => 'crown', 'text' => "Premium berakhir sekitar {$days} hari lagi", 'url' => route('account.subscription')];
        }

        return $items;
    }

    /** Riwayat singkat di topbar: aktivitas terbaru. Tiap item: icon, text, time, url. Terbaru di atas. */
    private function history(User $user, bool $isAdminArea): array
    {
        $rows = collect();

        if ($isAdminArea) {
            if ($user->canAccess('attempts') && Route::has('admin.attempts.show')) {
                ExamAttempt::with(['user', 'type'])->whereNotNull('finished_at')->latest('finished_at')->limit(4)->get()
                    ->each(fn ($a) => $rows->push([
                        'at' => $a->finished_at, 'icon' => 'bars', 'url' => route('admin.attempts.show', $a),
                        'text' => ($a->user?->name ?? 'Tamu').' menyelesaikan '.($a->type->name ?? 'ujian').' (skor '.$a->total_score.')',
                    ]));
            }
            if ($user->canAccess('orders') && Route::has('admin.orders.show')) {
                Order::with('user')->latest('id')->limit(3)->get()
                    ->each(fn ($o) => $rows->push([
                        'at' => $o->created_at, 'icon' => 'cart', 'url' => route('admin.orders.show', $o),
                        'text' => 'Pesanan '.$o->invoice_no.' dari '.($o->user?->name ?? 'tamu'),
                    ]));
            }
            if ($user->canAccess('users') && Route::has('admin.users.show')) {
                User::where('role', 'member')->latest('id')->limit(3)->get()
                    ->each(fn ($u) => $rows->push([
                        'at' => $u->created_at, 'icon' => 'users', 'url' => route('admin.users.show', $u),
                        'text' => $u->name.' mendaftar',
                    ]));
            }
        } else {
            if (Route::has('exam.result')) {
                ExamAttempt::with('type')->where('user_id', $user->id)->whereNotNull('finished_at')->latest('finished_at')->limit(5)->get()
                    ->each(fn ($a) => $rows->push([
                        'at' => $a->finished_at, 'icon' => 'bars', 'url' => route('exam.result', $a),
                        'text' => ($a->type->name ?? 'Ujian').' selesai, skor '.$a->total_score,
                    ]));
            }
            if (Route::has('billing.invoice')) {
                Order::where('user_id', $user->id)->latest('id')->limit(2)->get()
                    ->each(fn ($o) => $rows->push([
                        'at' => $o->created_at, 'icon' => 'card', 'url' => route('billing.invoice', $o),
                        'text' => 'Tagihan '.$o->invoice_no.' ('.($o->status === 'paid' ? 'lunas' : 'belum lunas').')',
                    ]));
            }
        }

        return $rows->filter(fn ($r) => $r['at'])->sortByDesc('at')->take(6)
            ->map(fn ($r) => ['icon' => $r['icon'], 'text' => $r['text'], 'url' => $r['url'], 'time' => $r['at']->diffForHumans()])
            ->values()->all();
    }

    /** Pembayaran yang sudah dikonfirmasi pembeli dan menunggu dicek admin. */
    private function waitingOrders(): int
    {
        return Order::where('status', 'pending')->whereNotNull('meta->claimed_at')->count();
    }
}
