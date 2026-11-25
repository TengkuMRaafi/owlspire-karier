<?php

namespace App\Services\Billing;

use App\Models\Order;

/** Menerapkan hasil dari gateway (webhook atau cek status) ke pesanan. Aman dipanggil berulang. */
class OrderSettler
{
    public function __construct(private EntitlementService $entitlements)
    {
    }

    /** @return bool false bila nominal tidak cocok (jangan beri akses). */
    public function apply(Order $order, array $result): bool
    {
        if (isset($result['amount']) && (int) $result['amount'] !== (int) $order->amount) {
            return false;
        }

        if ($result['status'] === 'paid' && $order->status !== 'paid') {
            $order->update(['status' => 'paid', 'paid_at' => now()]);
            $this->entitlements->grantFor($order->refresh());
        } elseif (in_array($result['status'], ['expired', 'failed'], true) && $order->status === 'pending') {
            $order->update(['status' => $result['status']]);
        } elseif (($result['attempt'] ?? null) === 'failed' && $order->status === 'pending') {
            // Dipindai tapi pembayaran gagal: tagihan tetap berjalan sampai kedaluwarsa, hanya dicatat.
            $order->update(['meta' => array_merge($order->meta ?? [], ['attempt_failed_at' => now()->toIso8601String()])]);
        }

        return true;
    }

    /** Tagihan menunggu yang batas waktunya lewat: tutup (kedaluwarsa) dan QR tidak berlaku lagi. Panggil SETELAH cek terakhir ke gateway. */
    public function expireIfDue(Order $order): bool
    {
        if ($order->status === 'pending' && $order->expires_at && $order->expires_at->isPast()) {
            $order->update(['status' => 'expired']);

            return true;
        }

        return false;
    }
}
