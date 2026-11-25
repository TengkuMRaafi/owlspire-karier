<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Midtrans. Utama: QRIS lewat Core API (QR tampil di halaman tagihan sendiri; acquirer GoPay, bisa dibayar dari e-wallet/m-banking apa saja).
 * Bila channel QRIS belum aktif di akun (402), otomatis memakai Snap (mode redirect). MIDTRANS_METHOD=snap memaksa Snap.
 * Status selalu dicek lewat Core API.
 */
class MidtransGateway implements PaymentGateway
{
    private function key(): string
    {
        return (string) config('platform.payment.midtrans.server_key');
    }

    /** Host Core API: dipakai untuk cek status. */
    private function base(): string
    {
        return config('platform.payment.midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    /** Host Snap: dipakai untuk membuat tagihan. */
    private function snapBase(): string
    {
        return config('platform.payment.midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
    }

    public function createCharge(Order $order): array
    {
        if ($this->key() === '') {
            throw new \RuntimeException('MIDTRANS_SERVER_KEY belum diisi di .env.');
        }

        if (config('platform.payment.midtrans.method', 'qris') === 'snap') {
            return $this->createSnap($order);
        }

        try {
            return $this->createQris($order);
        } catch (\RuntimeException $e) {
            // 402 = channel QRIS belum diaktifkan di akun. Pakai Snap supaya pembeli tetap bisa membayar.
            if ($e->getCode() !== 402) {
                throw $e;
            }
            Log::warning('Midtrans QRIS belum aktif, memakai Snap: '.$e->getMessage());

            return $this->createSnap($order);
        }
    }

    /** QRIS dinamis lewat Core API: balasannya berisi qr_string yang digambar di halaman tagihan. */
    private function createQris(Order $order): array
    {
        $user = $order->user;
        $res = Http::withBasicAuth($this->key(), '')->acceptJson()->asJson()->timeout(20)
            ->post($this->base().'/v2/charge', [
                'payment_type' => 'qris',
                'transaction_details' => ['order_id' => $order->invoice_no, 'gross_amount' => (int) $order->amount],
                'qris' => ['acquirer' => 'gopay'],
                'item_details' => [[
                    'id' => $order->item_type.'-'.$order->item_id,
                    'price' => (int) $order->amount, 'quantity' => 1,
                    'name' => mb_substr($order->meta['item_name'] ?? 'Owlspire Premium', 0, 50),
                ]],
                'customer_details' => array_filter(['first_name' => $user?->name, 'email' => $user?->email ?? ($order->meta['email'] ?? null)]),
                'custom_expiry' => [
                    'expiry_duration' => (int) config('platform.payment.midtrans.expiry_minutes', 60),
                    'unit' => 'minute',
                ],
            ]);

        $json = $res->json() ?? [];
        $code = (string) ($json['status_code'] ?? $res->status());

        if (! $res->successful() || ! in_array($code, ['200', '201'], true)) {
            throw new \RuntimeException('Midtrans menolak pembuatan tagihan: '.($json['status_message'] ?? $res->status()), $code === '402' ? 402 : 0);
        }

        $qrUrl = collect($json['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url'] ?? null;

        return [
            'gateway_ref' => $json['transaction_id'] ?? null,
            'qr_string' => $json['qr_string'] ?? null,
            'qr_url' => $qrUrl,
            'expiry_time' => $json['expiry_time'] ?? null,
        ];
    }

    /** Snap (redirect): pembeli memilih metode yang aktif di akun di halaman pembayaran Midtrans. */
    private function createSnap(Order $order): array
    {
        $user = $order->user;
        $res = Http::withBasicAuth($this->key(), '')->acceptJson()->asJson()->timeout(20)
            ->post($this->snapBase().'/snap/v1/transactions', [
                'transaction_details' => ['order_id' => $order->invoice_no, 'gross_amount' => (int) $order->amount],
                'item_details' => [[
                    'id' => $order->item_type.'-'.$order->item_id,
                    'price' => (int) $order->amount, 'quantity' => 1,
                    'name' => mb_substr($order->meta['item_name'] ?? 'Owlspire Premium', 0, 50),
                ]],
                'customer_details' => array_filter(['first_name' => $user?->name, 'email' => $user?->email ?? ($order->meta['email'] ?? null)]),
                'expiry' => [
                    'unit' => 'minutes',
                    'duration' => (int) config('platform.payment.midtrans.expiry_minutes', 60),
                ],
                // Setelah bayar (atau tutup halaman Midtrans), pembeli kembali ke halaman tagihan.
                'callbacks' => ['finish' => route('billing.invoice', $order)],
            ]);

        $json = $res->json() ?? [];
        if (! $res->successful() || empty($json['token']) || empty($json['redirect_url'])) {
            $reason = ! empty($json['error_messages']) ? implode('; ', (array) $json['error_messages']) : ($json['status_message'] ?? $res->status());
            throw new \RuntimeException('Midtrans menolak pembuatan tagihan: '.$reason);
        }

        return [
            'gateway_ref' => $json['token'],
            'pay_url' => $json['redirect_url'],
        ];
    }

    public function handleWebhook(array $payload, array $headers): ?array
    {
        return $this->parse($payload);
    }

    public function fetchStatus(Order $order): ?array
    {
        if ($this->key() === '') {
            return null;
        }
        $res = Http::withBasicAuth($this->key(), '')->acceptJson()->timeout(15)
            ->get($this->base().'/v2/'.rawurlencode($order->invoice_no).'/status');

        if (! $res->successful()) {
            Log::warning('Midtrans cek status gagal', ['invoice' => $order->invoice_no, 'http' => $res->status(), 'host' => $this->base(), 'body' => mb_substr($res->body(), 0, 300)]);

            return null;
        }

        $json = $res->json() ?? [];

        // Midtrans menjawab HTTP 200 dengan status_code 404 bila transaksi belum tercatat: artinya belum dibayar, bukan error.
        if ((string) ($json['status_code'] ?? '') === '404') {
            return ['invoice_no' => $order->invoice_no, 'status' => 'pending'];
        }

        // Jawaban ini kita ambil sendiri lewat HTTPS dengan server key, jadi tanda tangan tidak perlu diperiksa (itu untuk webhook masuk).
        // Yang wajib: jawaban memang untuk tagihan ini.
        $parsed = (string) ($json['order_id'] ?? '') === $order->invoice_no ? $this->parse($json, false) : null;
        if ($parsed === null) {
            // Format tidak dikenali atau bukan untuk tagihan ini.
            Log::info('Midtrans cek status tidak dipakai', ['invoice' => $order->invoice_no, 'status_code' => $json['status_code'] ?? null, 'transaction_status' => $json['transaction_status'] ?? null, 'ada_signature' => isset($json['signature_key']), 'message' => $json['status_message'] ?? null]);
        }

        return $parsed;
    }

    private function parse(array $p, bool $verifySignature = true): ?array
    {
        $required = ['order_id', 'status_code', 'gross_amount', 'transaction_status'];
        if ($verifySignature) {
            $required[] = 'signature_key';
        }
        foreach ($required as $f) {
            if (! isset($p[$f]) || ! is_scalar($p[$f])) {
                return null;
            }
        }

        if ($verifySignature) {
            $expected = hash('sha512', $p['order_id'].$p['status_code'].$p['gross_amount'].$this->key());
            if ($this->key() === '' || ! hash_equals($expected, (string) $p['signature_key'])) {
                return null;
            }
        }

        // Hanya settlement (uang benar-benar masuk) yang dianggap berhasil. Pembayaran yang gagal setelah QR dipindai
        // tidak menutup tagihan: tetap "menunggu" sampai batas waktu habis (expire), supaya pembeli bisa mencoba lagi.
        $status = match ($p['transaction_status']) {
            'settlement' => 'paid',
            'capture' => ($p['fraud_status'] ?? 'accept') === 'accept' ? 'paid' : 'pending',
            'expire' => 'expired',
            default => 'pending',
        };
        $attemptFailed = in_array($p['transaction_status'], ['cancel', 'deny', 'failure'], true);

        return ['invoice_no' => (string) $p['order_id'], 'status' => $status, 'amount' => (int) round((float) $p['gross_amount'])]
            + ($attemptFailed ? ['attempt' => 'failed'] : []);
    }
}
