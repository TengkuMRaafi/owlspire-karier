<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\ExamType;
use App\Models\Order;
use App\Models\Plan;
use App\Services\Account\PlanAccess;
use App\Services\Billing\EntitlementService;
use Illuminate\Http\Request;

/** Langganan: status paket, beli/perpanjang, dan riwayat pesanan. */
class SubscriptionController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements, PlanAccess $access)
    {
        // Admin dan staf tidak punya paket; halaman ini hanya untuk pengguna biasa.
        if ($request->user()->isInternal()) {
            return redirect()->route('dashboard.index')->with('status', 'Akunmu berperan '.$request->user()->roleLabel().': semua fitur terbuka tanpa paket.');
        }

        $uid = $request->user()->id;
        $premium = $entitlements->isPremium($uid);
        $types = ExamType::where('is_active', true)->orderBy('position')->orderBy('id')->get();

        return view('account.subscription', [
            'ticketOptions' => array_filter([
                'Seleksi resmi' => $types->where('kind', 'selection')->pluck('name', 'id')->all(),
                'Simulasi karier' => $types->where('kind', 'career')->pluck('name', 'id')->all(),
            ]),
            'premium' => $premium,
            'premiumUntil' => $premium ? $entitlements->premiumUntil($uid) : null,
            'features' => $access->features($premium),
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
            'tickets' => (int) Entitlement::where('user_id', $uid)->where('type', 'exam_pass')
                ->where('remaining', '>', 0)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->distinct()->count('reference_id'),
            'orders' => Order::where('user_id', $uid)->latest('id')->paginate(10)->withQueryString(),
            'periods' => ['month' => '/ bulan', 'quarter' => '/ 3 bulan', 'half' => '/ 6 bulan', 'year' => '/ tahun'],
        ]);
    }
}
