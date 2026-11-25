<?php

return [
    // Content-Security-Policy. Bawaan: aktif di produksi saja (di lokal Vite dev server butuh sumber tambahan).
    // Paksa lewat .env: SECURITY_CSP=true|false.
    'csp' => filter_var(env('SECURITY_CSP', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),

    // Wajibkan admin memakai verifikasi 2 langkah sebelum membuka panel admin.
    'require_admin_2fa' => filter_var(env('SECURITY_REQUIRE_ADMIN_2FA', false), FILTER_VALIDATE_BOOL),

    // Percobaan masuk yang salah sebelum dikunci sementara.
    'login_max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),          // per email + IP
    'login_max_attempts_email' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS_EMAIL', 12), // per email dari IP mana pun
    'login_lock_minutes' => (int) env('SECURITY_LOGIN_LOCK_MINUTES', 15),
];
