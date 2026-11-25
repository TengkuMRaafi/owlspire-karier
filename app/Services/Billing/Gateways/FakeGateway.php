<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGateway;
use App\Models\Order;

/** Hanya untuk development. JANGAN dipakai di produksi (PAYMENT_DRIVER=fake). */
class FakeGateway implements PaymentGateway
{
    public function createCharge(Order $order): array
    {
        return ['gateway_ref' => 'FAKE-'.$order->invoice_no];
    }

    public function handleWebhook(array $payload, array $headers): ?array
    {
        if (app()->environment('production') || empty($payload['invoice_no'])) {
            return null;
        }

        return ['invoice_no' => $payload['invoice_no'], 'status' => $payload['status'] ?? 'paid', 'amount' => null];
    }

    public function fetchStatus(Order $order): ?array
    {
        return null;
    }
}
