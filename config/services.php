<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Parafrase pembahasan dan ringkasan hasil ujian. Kosongkan GROQ_API_KEY untuk mematikan fitur.
    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'timeout' => (int) env('GROQ_TIMEOUT', 25),
    ],

    // Penyedia LLM cadangan (format OpenAI). Isi kuncinya untuk mengaktifkan; kosong = dilewati.
    // Urutan percobaan lewat LLM_ORDER. Nama model sering berganti, jadi cek dengan php artisan llm:test.
    'llm' => [
        'order' => env('LLM_ORDER', 'groq,gemini,cerebras,openrouter'),
        'budget' => (int) env('LLM_BUDGET', 24),
        // Urutan khusus untuk wawancara AI (mis. "gemini,groq,cerebras"). Kosong = pakai LLM_ORDER.
        'interview_order' => env('LLM_INTERVIEW_ORDER'),
        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 15),
        ],
        'cerebras' => [
            'key' => env('CEREBRAS_API_KEY'),
            'model' => env('CEREBRAS_MODEL', 'gpt-oss-120b'),
            'base_url' => env('CEREBRAS_BASE_URL', 'https://api.cerebras.ai/v1'),
            'timeout' => (int) env('CEREBRAS_TIMEOUT', 15),
        ],
        'openrouter' => [
            'key' => env('OPENROUTER_API_KEY'),
            'model' => env('OPENROUTER_MODEL', 'openai/gpt-oss-120b:free'),
            'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'timeout' => (int) env('OPENROUTER_TIMEOUT', 20),
        ],
    ],

    'youtube' => [
        'key' => env('YOUTUBE_API_KEY'),
    ],


    // Bot Telegram untuk pengingat belajar dan alert lowongan. Daftarkan webhook dengan: php artisan telegram:webhook
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // Google Apps Script yang membuat Google Sheet berisi semua hasil /cari (opsional; kosong = tombol Sheet tidak muncul).
    'jobs_sheet' => [
        'url' => env('JOBS_SHEET_URL'),
        'secret' => env('JOBS_SHEET_SECRET'),
    ],

    // Cloudflare Turnstile (CAPTCHA di Masuk, Daftar, dan Lupa sandi). Kosongkan kedua kunci untuk mematikannya.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

    // Masuk dengan Google (OAuth 2.0). Buat OAuth Client ID tipe "Web application" di Google Cloud Console, lalu isi
    // GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET. Kosongkan salah satunya untuk menyembunyikan tombolnya.
    // Redirect URI yang didaftarkan di Google harus sama persis dengan GOOGLE_REDIRECT_URI (bawaan: APP_URL/masuk/google/callback).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
];
