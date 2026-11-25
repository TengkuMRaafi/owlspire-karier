<?php

/*
 * Menu panel (sidebar) untuk dua area di dalam akun: admin dan pengguna.
 * Tidak ada tautan ke landing page di sini; semua item mengarah ke halaman di dalam akun.
 *
 *  route   : nama rute tujuan
 *  match   : pola rute yang membuat item tampil aktif (bawaan: rutenya sendiri)
 *  icon    : nama ikon di components/icon.blade.php
 *  premium : true = fitur Premium; pengguna Gratis melihat penanda PRO dan halaman terkunci
 *  badge   : 'orders' = jumlah pembayaran yang menunggu verifikasi
 *  area    : (admin) area di config/roles.php; item hanya tampil untuk peran yang boleh membukanya
 *  plan    : true = item paket (Langganan); disembunyikan untuk admin dan staf yang tidak punya paket
 */
return [
    'admin' => [
        'home' => 'admin.dashboard',
        'badge' => 'Admin',
        'groups' => [
            ['label' => 'Ringkasan', 'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'layout', 'area' => 'dashboard'],
                ['label' => 'Laporan', 'route' => 'admin.reports.index', 'icon' => 'file', 'match' => ['admin.reports.*'], 'area' => 'reports'],
            ]],
            ['label' => 'Simulasi', 'items' => [
                ['label' => 'Bank soal', 'route' => 'admin.questions.index', 'icon' => 'list', 'match' => ['admin.questions.*'], 'area' => 'questions'],
                ['label' => 'Jenis ujian', 'route' => 'admin.setup.index', 'icon' => 'layers', 'match' => ['admin.setup.*'], 'area' => 'exam_setup'],
                ['label' => 'Hasil ujian', 'route' => 'admin.attempts.index', 'icon' => 'bars', 'match' => ['admin.attempts.*'], 'area' => 'attempts'],
            ]],
            ['label' => 'Konten', 'items' => [
                ['label' => 'Karier', 'route' => 'admin.careers.index', 'icon' => 'briefcase', 'match' => ['admin.careers.*'], 'area' => 'careers'],
                ['label' => 'Video belajar', 'route' => 'admin.learning.index', 'icon' => 'play', 'match' => ['admin.learning.*'], 'area' => 'learning'],
                ['label' => 'Broadcast', 'route' => 'admin.broadcast.index', 'icon' => 'mail', 'match' => ['admin.broadcast.*'], 'area' => 'broadcast'],
            ]],
            ['label' => 'Bisnis', 'items' => [
                ['label' => 'Pesanan', 'route' => 'admin.orders.index', 'icon' => 'cart', 'match' => ['admin.orders.*'], 'badge' => 'orders', 'area' => 'orders'],
                ['label' => 'Pengguna', 'route' => 'admin.users.index', 'icon' => 'users', 'match' => ['admin.users.*'], 'area' => 'users'],
            ]],
            ['label' => 'Sistem', 'items' => [
                ['label' => 'Log aktivitas', 'route' => 'admin.audit.index', 'icon' => 'shield', 'match' => ['admin.audit.*'], 'area' => 'audit'],
                ['label' => 'Pengaturan', 'route' => 'admin.settings', 'icon' => 'sliders', 'match' => ['admin.settings*', 'admin.plans.*']],
            ]],
        ],
    ],

    'account' => [
        'home' => 'dashboard.index',
        'badge' => null,
        'groups' => [
            ['label' => 'Ringkasan', 'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'layout'],
            ]],
            ['label' => 'Belajar & karier', 'items' => [
                ['label' => 'Simulasi', 'route' => 'account.exams', 'icon' => 'play', 'match' => ['account.exams*']],
                ['label' => 'Riwayat', 'route' => 'account.history', 'icon' => 'history', 'match' => ['account.history*']],
                ['label' => 'Rencana belajar', 'route' => 'account.plan', 'icon' => 'target', 'match' => ['account.plan*'], 'premium' => (bool) env('PREMIUM_ONLY_PLAN', true)],
                ['label' => 'Info seleksi', 'route' => 'account.selections', 'icon' => 'calendar', 'match' => ['account.selections*']],
                ['label' => 'Belajar', 'route' => 'account.learning', 'icon' => 'book', 'match' => ['account.learning*']],
                ['label' => 'Karier saya', 'route' => 'account.career', 'icon' => 'target', 'match' => ['account.career*']],
                ['label' => 'Asisten AI', 'route' => 'account.chat', 'icon' => 'sparkle', 'match' => ['account.chat*']],
            ]],
            ['label' => 'Lamaran kerja', 'items' => [
                ['label' => 'Pelacak lamaran', 'route' => 'account.applications', 'icon' => 'briefcase', 'match' => ['account.applications*', 'account.alerts*']],
                ['label' => 'Analisis CV', 'route' => 'cv.index', 'icon' => 'file', 'match' => ['cv.*'], 'premium' => true],
                ['label' => 'Wawancara AI', 'route' => 'account.interview', 'icon' => 'users', 'match' => ['account.interview*'], 'premium' => (bool) env('PREMIUM_ONLY_INTERVIEW', true)],
            ]],
            ['label' => 'Akun', 'items' => [
                ['label' => 'Langganan', 'route' => 'account.subscription', 'icon' => 'crown', 'match' => ['account.subscription*'], 'plan' => true],
                ['label' => 'Pengaturan', 'route' => 'account.settings', 'icon' => 'sliders', 'match' => ['account.settings*']],
            ]],
        ],
    ],
];
