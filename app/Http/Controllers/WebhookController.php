<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Services\Billing\OrderSettler;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request, string $gateway, PaymentGateway $gw, OrderSettler $settler)
    {
        $result = $gw->handleWebhook($request->all(), $request->headers->all());

        $order = $result ? Order::where('invoice_no', $result['invoice_no'])->first() : null;
        PaymentEvent::create([
            'order_id' => $order?->id,
            'gateway' => $gateway,
            'event_type' => $result['status'] ?? 'invalid',
            'payload' => $request->all(),
            'processed_at' => $result ? now() : null,
        ]);

        if (! $result || ! $order) {
            return response()->json(['ok' => false], 400);
        }

        // Nominal dari gateway harus sama dengan tagihan; kalau tidak, jangan beri akses.
        if (! $settler->apply($order, $result)) {
            return response()->json(['ok' => false, 'message' => 'amount mismatch'], 400);
        }

        return response()->json(['ok' => true]);
    }
}
