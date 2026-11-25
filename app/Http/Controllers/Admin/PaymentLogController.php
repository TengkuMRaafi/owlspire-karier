<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use Illuminate\Http\Request;

/** Log mentah webhook pembayaran (read-only), untuk menelusuri pesanan yang tidak otomatis lunas. Area `orders`. */
class PaymentLogController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentEvent::with('order')->latest('id');
        if ($q = trim((string) $request->query('cari'))) {
            $query->where(fn ($w) => $w->whereHas('order', fn ($o) => $o->where('invoice_no', 'like', "%{$q}%"))->orWhere('event_type', 'like', "%{$q}%"));
        }
        if ($gw = $request->query('gateway')) {
            $query->where('gateway', $gw);
        }
        if ($request->query('status') === 'gagal') {
            $query->whereNull('processed_at');
        } elseif ($request->query('status') === 'diproses') {
            $query->whereNotNull('processed_at');
        }

        return view('admin.payments.index', [
            'events' => $query->paginate(20)->withQueryString(),
            'gateways' => PaymentEvent::distinct()->pluck('gateway', 'gateway')->all(),
        ]);
    }
}
