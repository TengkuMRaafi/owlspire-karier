<?php

/*
 * Skema preferensi pengguna (halaman Pengaturan di dashboard akun).
 * Satu-satunya sumber: form, validasi, dan nilai bawaan dibaca dari sini oleh App\Services\Account\Preferences.
 *
 *  route   : menu terkait (tombol "Buka menu" di kartu pengaturan), opsional
 *  type    : bool (saklar) | choice (pilihan tunggal)
 *  cast    : untuk choice, 'int' bila kunci opsi berupa angka
 *  default : nilai bila pengguna belum pernah menyimpan
 *  options : khusus choice, kunci => label
 *
 * Tambah kunci baru di sini, lalu pakai lewat $user->pref('grup.kunci'). Jangan menambah kunci yang
 * belum dipakai fitur mana pun, supaya setiap saklar di halaman Pengaturan benar-benar berpengaruh.
 */
return [
    'exam' => [
        'route' => 'account.exams',
        'title' => 'Simulasi',
        'icon' => 'play',
        'desc' => 'Atur ruang ujian. Berlaku untuk simulasi karier, seleksi resmi, dan mode coba.',
        'fields' => [
            'timer' => [
                'type' => 'choice', 'default' => 'always', 'label' => 'Tampilan timer',
                'hint' => 'Waktu tetap berjalan dan ujian selesai otomatis saat habis, walau timer disembunyikan.',
                'options' => ['always' => 'Selalu tampil', 'last5' => '5 menit terakhir'],
            ],
            'confirm_finish' => [
                'type' => 'choice', 'default' => 'always', 'label' => 'Konfirmasi saat menyelesaikan',
                'hint' => 'Jawaban tidak bisa diubah setelah ujian selesai.',
                'options' => ['always' => 'Selalu tanya', 'unanswered' => 'Hanya jika ada soal kosong'],
            ],
            'auto_next' => [
                'type' => 'bool', 'default' => false, 'label' => 'Lanjut otomatis',
                'hint' => 'Pindah ke soal berikutnya begitu kamu memilih jawaban.',
            ],
            'large_text' => [
                'type' => 'bool', 'default' => false, 'label' => 'Teks soal lebih besar',
                'hint' => 'Memperbesar soal dan pilihan jawaban di ruang ujian.',
            ],
        ],
    ],

    'learning' => [
        'route' => 'account.learning',
        'title' => 'Belajar',
        'icon' => 'book',
        'desc' => 'Target belajar yang kemajuannya tampil di halaman Belajar.',
        'fields' => [
            'weekly_goal' => [
                'type' => 'choice', 'cast' => 'int', 'default' => 0, 'label' => 'Target topik per minggu',
                'hint' => 'Minggu dihitung mulai Senin. Topik yang kamu tandai selesai ikut terhitung.',
                'options' => [0 => 'Tanpa target', 3 => '3 topik', 5 => '5 topik', 7 => '7 topik', 10 => '10 topik'],
            ],
        ],
    ],

    'assistant' => [
        'route' => 'account.chat',
        'title' => 'Asisten AI',
        'icon' => 'sparkle',
        'desc' => 'Cara Asisten AI menjawab. Tidak mengubah kuota pesan harianmu.',
        'fields' => [
            'style' => [
                'type' => 'choice', 'default' => 'short', 'label' => 'Panjang jawaban',
                'hint' => 'Jawaban rinci lebih panjang dan bisa memakai langkah bernomor.',
                'options' => ['short' => 'Ringkas', 'detail' => 'Rinci'],
            ],
            'tone' => [
                'type' => 'choice', 'default' => 'casual', 'label' => 'Gaya bahasa',
                'options' => ['casual' => 'Santai', 'formal' => 'Formal'],
            ],
            'use_profile' => [
                'type' => 'bool', 'default' => true, 'label' => 'Personalisasi jawaban',
                'hint' => 'Kirim namamu, karier target, dan keterangan profil ke AI supaya saran lebih relevan. Matikan bila tidak ingin data itu dikirim.',
            ],
        ],
    ],

    'notifications' => [
        'title' => 'Notifikasi',
        'icon' => 'bell',
        'desc' => 'Pilih hal yang muncul di lonceng pada bilah atas dashboard.',
        'fields' => [
            'exam_running' => ['type' => 'bool', 'default' => true, 'label' => 'Ujian yang belum selesai'],
            'billing' => ['type' => 'bool', 'default' => true, 'label' => 'Tagihan belum dibayar'],
            'new_device' => ['type' => 'bool', 'default' => true, 'label' => 'Login dari perangkat baru', 'hint' => 'Muncul selama 3 hari setelah akunmu dipakai masuk dari perangkat yang belum dikenal.'],
            'premium_end' => ['type' => 'bool', 'default' => true, 'label' => 'Premium hampir berakhir', 'hint' => 'Muncul 7 hari sebelum masa aktif habis.'],
        ],
    ],

    'dashboard' => [
        'route' => 'dashboard.index',
        'title' => 'Dashboard',
        'icon' => 'layout',
        'desc' => 'Pilih bagian yang tampil di halaman Dashboard. Angka ringkasan di bagian atas selalu tampil.',
        'fields' => [
            'show_readiness' => ['type' => 'bool', 'default' => true, 'label' => 'Skor kesiapan karier'],
            'show_history' => ['type' => 'bool', 'default' => true, 'label' => 'Riwayat terakhir'],
            'show_cv' => ['type' => 'bool', 'default' => true, 'label' => 'Ringkasan analisis CV'],
            'show_access' => ['type' => 'bool', 'default' => true, 'label' => 'Daftar akses Gratis dan Premium'],
        ],
    ],

    'privacy' => [
        'route' => 'exam.leaderboard',
        'title' => 'Privasi',
        'icon' => 'shield',
        'desc' => 'Atur apa yang bisa dilihat pengguna lain.',
        'fields' => [
            'leaderboard' => [
                'type' => 'bool', 'default' => true, 'label' => 'Tampil di papan peringkat',
                'hint' => 'Namamu sudah disamarkan (nama depan dan inisial). Matikan untuk tidak muncul sama sekali; skormu tetap tersimpan.',
            ],
        ],
    ],
];
