<?php

namespace App\Contracts;

use App\Models\Order;

/** Pindah gateway (Midtrans/Xendit/Tripay) cukup menambah satu class yang mengimplementasikan */
interface PaymentGateway
{
    /** Membuat tagihan di gateway. */
    public function createCharge(Order $order): array;

    /** Memverifikasi tanda tangan webhook lalu mengembalikan ['invoice_no' => string, 'status' */
    public function handleWebhook(array $payload, array $headers): ?array;

    /** Menanyakan status ke gateway (cadangan bila webhook terlambat). */
    public function fetchStatus(Order $order): ?array;
}
