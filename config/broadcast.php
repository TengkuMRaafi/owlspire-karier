<?php

/*
 * Broadcast email dari admin. Dikirim lewat antrean (QUEUE_CONNECTION=database), jadi jalankan `php artisan queue:work`.
 *
 *  delay_seconds : jeda antar email supaya SMTP tidak kewalahan (1 = sekitar 60 email per menit)
 *  daily_limit   : batas kiriman harian SMTP (Brevo gratis sekitar 300). Hanya untuk peringatan di halaman admin.
 */
return [
    'delay_seconds' => (int) env('BROADCAST_DELAY_SECONDS', 1),
    'daily_limit' => (int) env('BROADCAST_DAILY_LIMIT', 300),
];
