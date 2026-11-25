<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Billing\EntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user')->latest('id');
        $status = $request->query('status');
        if (in_array($status, ['pending', 'paid', 'failed', 'expired'], true)) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('cari'))) {
            $query->where(fn ($q) => $q->where('invoice_no', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(10)->withQueryString(),
            'counts' => Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'manual' => $this->manualAllowed(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load('user');

        return view('admin.orders.show', ['order' => $order, 'manual' => $this->manualAllowed()]);
    }

    /** Dengan Midtrans, lunas/gagal/kedaluwarsa diputuskan otomatis oleh gateway; verifikasi manual hanya untuk mode tanpa gateway. */
    private function manualAllowed(): bool
    {
        return config('platform.payment.driver') !== 'midtrans';
    }

    public function markPaid(Order $order, EntitlementService $entitlements): RedirectResponse
    {
        if ($order->status === 'paid') {
            return back()->with('error', 'Pesanan ini sudah lunas dan statusnya terkunci.');
        }
        if (! $this->manualAllowed()) {
            return back()->with('error', 'Status pesanan diperbarui otomatis oleh Midtrans, tidak bisa diubah manual.');
        }
        if ($order->status !== 'pending') {
            return back()->with('error', 'Pesanan yang sudah ditolak atau kedaluwarsa tidak bisa ditandai lunas.');
        }

        $order->update(['status' => 'paid', 'paid_at' => now(), 'meta' => array_merge($order->meta ?? [], ['verified_by' => auth()->id()])]);
        $entitlements->grantFor($order->refresh());

        return back()->with('status', "Pesanan {$order->invoice_no} dilunasi dan tiket sudah diberikan.");
    }

    public function reject(Order $order): RedirectResponse
    {
        if (! $this->manualAllowed()) {
            return back()->with('error', 'Status pesanan diperbarui otomatis oleh Midtrans, tidak bisa diubah manual.');
        }
        if ($order->status !== 'pending') {
            return back()->with('error', 'Hanya pesanan yang masih menunggu yang bisa ditolak.');
        }
        $order->update(['status' => 'failed']);

        return back()->with('status', "Pesanan {$order->invoice_no} ditolak.");
    }
}
