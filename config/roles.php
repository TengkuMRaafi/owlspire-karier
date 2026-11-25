<?php

/*
 * Peran akun dan area panel yang boleh dibuka tiap peran. Sumber tunggal: rute admin (middleware `area:...`),
 * menu sidebar (config/panel.php > area), dan tabel "Peran dan akses" di Pengaturan admin membaca dari sini.
 *
 *  admin  : pemilik sistem. Akses penuh, tanpa paket Gratis/Premium, peran terkunci (tidak bisa diubah dari mana pun).
 *  staff  : di bawah admin, dibuat oleh admin. Mengelola konten; tidak menyentuh uang, pengguna, atau struktur ujian.
 *  member : pengguna biasa. Paket Gratis atau Premium mengikuti langganan aktif, bukan role.
 */
return [
    'roles' => [
        'admin' => ['label' => 'Admin', 'desc' => 'Akses penuh ke panel dan semua fitur pengguna. Tidak memakai paket. Peran terkunci.'],
        'staff' => ['label' => 'Staf', 'desc' => 'Mengelola soal, karier, video belajar, dan hasil ujian. Akses penuh ke fitur pengguna untuk meninjau.'],
        'member' => ['label' => 'Pengguna', 'desc' => 'Pengguna biasa. Paket Gratis atau Premium mengikuti langganan aktif.'],
    ],

    // Role yang boleh diberikan lewat halaman Pengguna. Admin sengaja tidak ada: dibuat hanya lewat `php artisan owlspire:admin`.
    'assignable' => ['member', 'staff'],

    // Akun internal: akses penuh ke fitur pengguna, jadi tidak punya paket Gratis/Premium.
    'internal' => ['admin', 'staff'],

    // Area panel => peran yang boleh masuk.
    'areas' => [
        'dashboard' => ['admin', 'staff'],
        'reports' => ['admin', 'staff'],
        'questions' => ['admin', 'staff'],
        'attempts' => ['admin', 'staff'],
        'careers' => ['admin', 'staff'],
        'learning' => ['admin', 'staff'],
        'exam_setup' => ['admin'],
        'orders' => ['admin'],
        'users' => ['admin'],
        'broadcast' => ['admin'],
        'audit' => ['admin'],
        'system' => ['admin'],
    ],

    // Area yang boleh diberikan ke staf dari Pengaturan > Peran dan akses. Pengguna, Log aktivitas, dan Sistem
    // sengaja tidak ada di sini: itu wilayah admin. Pilihan admin disimpan di tabel settings (kunci staff_areas).
    'staff_grantable' => ['dashboard', 'reports', 'questions', 'attempts', 'careers', 'learning', 'exam_setup', 'orders'],

    // Nama area untuk tabel di Pengaturan admin.
    'area_labels' => [
        'dashboard' => 'Dashboard dan ringkasan',
        'reports' => 'Laporan dan rekap',
        'questions' => 'Bank soal dan impor soal',
        'attempts' => 'Hasil ujian',
        'careers' => 'Karier',
        'learning' => 'Video belajar',
        'exam_setup' => 'Jenis ujian dan segmen',
        'orders' => 'Pesanan dan pendapatan',
        'users' => 'Pengguna, role, Premium, dan tiket',
        'broadcast' => 'Broadcast email ke pengguna',
        'audit' => 'Log aktivitas',
        'system' => 'Fitur, kuota, harga, dan info sistem',
    ],
];
