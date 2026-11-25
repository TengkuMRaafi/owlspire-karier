<?php

namespace App\Console\Commands;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\Billing\OrderSettler;
use Illuminate\Console\Command;

class ExpireOrdersCommand extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Tutup tagihan yang melewati batas waktu bayar (QR tidak berlaku lagi); cek terakhir ke Midtrans dulu supaya pembayaran yang masuk di detik akhir tetap diterima';

    public function handle(PaymentGateway $gateway, OrderSettler $settler): int
    {
        $closed = 0;

        Order::where('status', 'pending')->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->chunkById(100, function ($orders) use ($gateway, $settler, &$closed) {
                foreach ($orders as $order) {
                    try {
                        if ($order->gateway === 'midtrans' && ($result = $gateway->fetchStatus($order))) {
                            $settler->apply($order, $result);
                            $order->refresh();
                        }
                    } catch (\Throwable $e) {
                        report($e);
                        // Gateway tidak terjangkau: jangan tutup dulu, coba lagi putaran berikutnya.
                        continue;
                    }

                    if ($settler->expireIfDue($order)) {
                        $closed++;
                    }
                }
            });

        $this->info("Tagihan ditutup: {$closed}");

        return self::SUCCESS;
    }
}
