<?php

return [
    // Identitas merek. Dibaca langsung dari sini, jadi tidak bergantung pada APP_NAME di .env.
    'brand' => [
        'name' => 'Owlspire',
        'tagline' => 'Career & Learning for Everyone',
        'description' => 'Latihan CPNS, BUMN, dan Polri/TNI dengan timer dan penilaian di server. Belajar dan lowongan kerja menyusul di satu tempat.',
    ],

    'locale' => 'id',

    // Menu Belajar: terbuka secara bawaan. Isi LEARNING_OPEN=false di .env (atau matikan di Pengaturan admin) untuk menampilkan "Segera Hadir".
    // Saat pengujian (APP_ENV=testing) menu ini otomatis terbuka supaya tes halaman Belajar tetap berjalan.
    'learning_open' => (bool) env('LEARNING_OPEN', true),

    // Bot Telegram OwlSpire.ID (versi baru, berjalan di webhook Laravel; bot Python lama ada di legacy/jobs-python).
    // Username tanpa @, boleh juga berupa tautan t.me. Nama tampilan diatur dengan: php artisan telegram:setup
    'telegram' => [
        'name' => env('TELEGRAM_BOT_NAME', 'OwlSpire.ID'),
        // Teks profil bot (diterapkan dengan: php artisan telegram:setup). Batas Telegram: deskripsi 512 huruf, about 120 huruf.
        'description' => env('TELEGRAM_BOT_DESCRIPTION', 'Bot resmi OwlSpire.ID, Career & Learning for Everyone. Cari lowongan kerja dan magang lengkap dengan persen kecocokan skill, terima pengingat belajar dan alert lowongan harian, serta latihan simulasi CPNS, BUMN, dan Polri/TNI di situs kami. Tautkan akun untuk pengingat pribadi, atau tambahkan bot ke grup untuk info lowongan pukul 06.00 dan 18.00.'),
        'about' => env('TELEGRAM_BOT_ABOUT', 'Lowongan, magang, pengingat belajar, dan simulasi CPNS/BUMN/Polri-TNI dari OwlSpire.ID'),
        // Topik bawaan kiriman grup (pukul 06.00 dan 18.00) sebelum admin grup mengatur /topik sendiri.
        'group_positions' => array_values(array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_GROUP_POSITIONS', 'admin, customer service, staff'))))),
        'bot' => env('TELEGRAM_BOT_USERNAME') ?: 'jobfinder_id_bot',
        'channel' => env('TELEGRAM_CHANNEL_URL'),
    ],


    'exam' => [
        'full_max_questions' => (int) env('EXAM_FULL_MAX_QUESTIONS', 100), // batas soal satu ujian penuh (semua jenis ujian)
        'trial_questions_per_segment' => 5,
        'points_per_correct' => 5,
        'pass_price' => 9999,          // rupiah per tiket simulasi
        'pass_attempts' => (int) env('EXAM_PASS_ATTEMPTS', 2), // jatah ujian penuh per tiket; habis = simulasi terkunci lagi
        'pass_valid_days' => null,      // null = tiket tidak kedaluwarsa
        'grace_seconds' => 5,           // toleransi jaringan saat jawaban terakhir dikirim
        'order_expires_hours' => 24,
    ],

    // Papan skor di landing (antara hero dan roadmap). Angka pengguna, simulasi, dan soal dibaca dari database.
    // Rating belum punya sumber data di aplikasi, jadi diisi manual lewat .env (mis. PLATFORM_RATING=4.8).
    // PLATFORM_SCOREBOARD_DEMO=true (bawaan) memakai angka contoh dari 'demo' supaya landing terlihat hidup
    // sebelum data nyata terkumpul. Ganti ke false saat peluncuran agar angka diambil dari database.
    'scoreboard' => [
        'demo' => (bool) env('PLATFORM_SCOREBOARD_DEMO', true),
        'rating' => env('PLATFORM_RATING') !== null && env('PLATFORM_RATING') !== '' ? (float) env('PLATFORM_RATING') : null,
        'rating_count' => env('PLATFORM_RATING_COUNT') !== null && env('PLATFORM_RATING_COUNT') !== '' ? (int) env('PLATFORM_RATING_COUNT') : null,
        'demo_values' => [
            'rating' => 4.8,
            'rating_count' => 2431,
            'pengguna' => 12480,
            'selesai' => 48250,
            'soal' => 3600,
        ],
    ],

    // Papan karier di landing: urutan dibekukan per jendela hari, dihitung dari data nyata.
    'trending' => [
        'refresh_days' => (int) env('TRENDING_REFRESH_DAYS', 7), // urutan diperbarui tiap N hari (mingguan)
        // Lowongan yang dihitung hanya dari sumber resmi (API/feed yang disediakan situsnya); hasil scraping tidak ikut.
        'job_sources' => ['linkedin', 'jobicy', 'remoteok', 'arbeitnow'],
        'job_days' => 30,       // lowongan dihitung N hari ke belakang
        'sim_days' => 14,       // simulasi selesai dihitung N hari ke belakang
        'weight_job' => 1,
        'weight_sim' => 3,
        'weight_target' => 5,
    ],

    // Simulasi karier: 15 soal gratis per karier (3 bagian x 5).
    'career_exam' => [
        'trial_per_segment' => 5,
        'full_total' => 100,           // ujian penuh: 100 soal diacak dari bank (34/33/33 antar bagian)
        'duration_seconds' => 6000,    // 100 menit untuk 100 soal
        'trial_duration_seconds' => 900,
        'min_percent' => 70,
    ],

    // Zona waktu tanggal diskon yang diisi admin (disimpan UTC, ditampilkan di zona ini).
    'promo' => [
        'timezone' => env('PROMO_TIMEZONE', 'Asia/Jakarta'),
    ],

    // Paket Premium (harga contoh, ubah sesuai kebutuhan). Disimpan ke tabel plans lewat PlanSeeder.
    'premium' => [
        'plans' => [
            ['code' => 'premium-1m', 'name' => 'Premium 1 Bulan', 'price' => 29000, 'interval' => 'month'],
            ['code' => 'premium-3m', 'name' => 'Premium 3 Bulan', 'price' => 69000, 'interval' => 'quarter'],
            ['code' => 'premium-6m', 'name' => 'Premium 6 Bulan', 'price' => 138000, 'interval' => 'half', 'follow_quarter' => true], // otomatis 2x harga 3 bulan
        ],
        // Daftar "Gratis" di halaman Harga. Bisa diubah admin (Pengaturan > Pembayaran > Keuntungan Premium).
        'free_features' => [
            '15 soal per simulasi, tiap karier dan tiap seleksi',
            'Timer dan penilaian di server',
            'Pembahasan lengkap setelah selesai',
            'Jalur belajar dasar per karier',
            'Tidak perlu akun untuk mencoba',
        ],
        'features' => [
            'Ujian penuh tanpa batas: CPNS, BUMN, Polri/TNI, dan semua simulasi karier',
            'Soal level lanjut, lebih kompleks, dengan passing grade dan peringkat',
            'Wawancara AI dengan penilaian, saran, dan contoh jawaban (HR, teknis, BUMN, situasional)',
            'Analisis arah karier lengkap: alasan, profil dimensi, celah, dan langkah berikutnya',
            'Analisis CV: skill terbaca otomatis, kecocokan karier, dan celah skill',
            'Asisten AI dengan kuota harian lebih besar',
            'Jalur belajar lengkap per karier dan skor kesiapan karier',
        ],
    ],

    // Matriks akses Gratis vs Premium. Sama dengan bagian "Bandingkan Gratis dengan Premium" di landing;
    // dipakai dashboard pengguna dan halaman Langganan supaya pembedanya selalu konsisten.
    'access' => [
        'free' => [
            ['icon' => 'play', 'label' => 'Simulasi karier', 'desc' => '15 soal tiap karier, timer dihitung server, lengkap dengan pembahasan.', 'route' => 'account.exams'],
            ['icon' => 'compass', 'label' => 'Tes arah karier', 'desc' => '20 pertanyaan keahlian yang spesifik; karier paling cocok terbuka gratis, alasan dan profil lengkap untuk Premium.', 'route' => 'careers.quiz'],
            ['icon' => 'play', 'label' => 'Coba simulasi umum', 'desc' => 'Soal pengetahuan dasar untuk merasakan alur simulasi. Mengerjakan gratis, skor dan pembahasan Premium.', 'route' => 'careers.quiz'],
            ['icon' => 'target', 'label' => 'Skor kesiapan', 'desc' => 'Pasang satu karier target, lalu pantau skor 0 sampai 100.', 'route' => 'account.career'],
            ['icon' => 'book', 'label' => 'Jalur belajar', 'desc' => 'Dua topik pertama tiap jalur terbuka. Video YouTube dibungkus pemutar Owlspire.', 'route' => 'account.learning'],
            ['icon' => 'briefcase', 'label' => 'Daftar karier', 'desc' => 'Ringkasan peran dan skill yang dibutuhkan untuk tiap karier.', 'route' => 'account.career'],
        ],
        'premium' => [
            ['icon' => 'shield', 'label' => 'Seleksi resmi', 'desc' => 'CPNS (SKD), BUMN, dan Polri/TNI dengan durasi dan passing grade seperti aslinya.', 'route' => 'account.exams'],
            ['icon' => 'sparkle', 'label' => 'Soal level sulit', 'desc' => 'Soal kasus lanjutan di setiap simulasi karier, untuk mengukur kesiapan sebenarnya.', 'route' => 'account.exams'],
            ['icon' => 'compass', 'label' => 'Analisis arah karier lengkap', 'desc' => 'Tiga karier teratas dengan alasan dari jawabanmu, profil tujuh dimensi, celah, dan langkah berikutnya.', 'route' => 'careers.quiz'],
            ['icon' => 'file', 'label' => 'Analisis CV', 'desc' => 'Skill terbaca otomatis, dicocokkan ke karier, dan celahnya ditunjukkan.', 'route' => 'cv.index'],
            ['icon' => 'layers', 'label' => 'Semua topik belajar', 'desc' => 'Seluruh materi di tiap jalur belajar terbuka, tanpa kunci.', 'route' => 'account.learning'],
            ['icon' => 'trophy', 'label' => 'Papan peringkat', 'desc' => 'Skormu masuk papan peringkat dan mendapat status lulus passing grade.', 'route' => 'account.exams'],
        ],
    ],

    // Asisten AI di dashboard (Groq). Kuota dihitung per hari; ubah lewat .env tanpa menyentuh kode.
    'chatbot' => [
        'free_daily' => (int) env('CHATBOT_FREE_DAILY', 10),
        'premium_daily' => (int) env('CHATBOT_PREMIUM_DAILY', 50),
        'max_chars' => 500,         // panjang satu pesan
        'context_messages' => 8,    // pesan terakhir yang ikut dikirim sebagai konteks
        'max_tokens' => 900,        // panjang jawaban (model gpt-oss memakai sebagian untuk berpikir)
    ],

    // Fitur khusus Premium. Ubah ke false (env PREMIUM_ONLY_*=false) untuk membukanya bagi semua pengguna. Admin dan staf selalu lolos.
    'premium_only' => [
        'plan' => (bool) env('PREMIUM_ONLY_PLAN', true),
        'interview' => (bool) env('PREMIUM_ONLY_INTERVIEW', true),
    ],

    // Alat AI (LLM gratis berantai di GroqClient). Kuota dihitung per hari; satu wawancara = satu sesi (5 pertanyaan).
    'ai_tools' => [
        'interview' => ['free_daily' => (int) env('INTERVIEW_FREE_DAILY', 1), 'premium_daily' => (int) env('INTERVIEW_PREMIUM_DAILY', 5)],
        // Analisis CV (baca PDF/DOCX/OCR memakai CPU). Fitur ini khusus Premium, jadi free_daily hanya cadangan.
        'cv_analysis' => ['free_daily' => (int) env('CV_ANALYSIS_FREE_DAILY', 0), 'premium_daily' => (int) env('CV_ANALYSIS_PREMIUM_DAILY', 5)],
    ],

    // Rencana belajar, streak, dan pengingat. Zona waktu dipakai untuk jam pengingat dan pergantian hari streak.
    'study' => [
        'timezone' => env('STUDY_TIMEZONE', 'Asia/Jakarta'),
        'goal_options' => [3, 5, 10, 15, 20],
    ],

    // Info seleksi: tautan portal resmi di halaman Info seleksi. Periksa lagi alamatnya sebelum rilis.
    'selection' => [
        'portals' => [
            ['name' => 'SSCASN (BKN)', 'desc' => 'Pendaftaran CPNS dan PPPK', 'url' => 'https://sscasn.bkn.go.id'],
            ['name' => 'Rekrutmen Bersama BUMN', 'desc' => 'Pendaftaran BUMN', 'url' => 'https://rekrutmenbersama.fhcibumn.id'],
            ['name' => 'Penerimaan Polri', 'desc' => 'Pendaftaran Polri', 'url' => 'https://penerimaan.polri.go.id'],
        ],
    ],

    'learning' => [
        // Sumber belajar terbuka. Lisensi dicek ulang di halaman masing-masing sebelum dipakai ulang.
        'sources' => [
            ['name' => 'freeCodeCamp', 'url' => 'https://www.youtube.com/@freecodecamp', 'license' => 'Video umumnya CC BY, cek keterangan tiap video'],
            ['name' => 'MIT OpenCourseWare', 'url' => 'https://ocw.mit.edu', 'license' => 'CC BY-NC-SA 4.0 pada sebagian besar materi'],
            ['name' => 'Khan Academy', 'url' => 'https://www.khanacademy.org', 'license' => 'Gratis; sebagian materi CC BY-NC-SA'],
            ['name' => 'Grow with Google', 'url' => 'https://grow.google', 'license' => 'Gratis diakses (bukan lisensi terbuka)'],
        ],
    ],

    'jobs' => [
        'max_detail' => 25,   // batas baca deskripsi LinkedIn per pencarian
        'max_running' => 3,   // pencarian yang boleh berjalan bersamaan
        'min_match' => 25,    // batas persen kecocokan yang dianggap cocok
        'free_results' => 5,  // lowongan yang tampil penuh untuk non-Premium (sisanya diburamkan)
    ],

    // Keamanan akun: batas perangkat yang boleh masuk bersamaan (admin dan staf tidak dibatasi).
    'security' => [
        'max_devices' => (int) env('MAX_DEVICES', 2),
        'notify_new_device' => (bool) env('NOTIFY_NEW_DEVICE', true),
    ],

    // Pengguna biasa bisa daftar lewat Google ATAU email + sandi (email wajib diverifikasi lewat link).
    // Set MEMBERS_GOOGLE_ONLY=true bila suatu saat ingin pengguna biasa hanya lewat Google (admin dan staf tetap email + sandi).
    'auth' => [
        'members_google_only' => (bool) env('MEMBERS_GOOGLE_ONLY', false),
    ],

    'payment' => [
        'driver' => env('PAYMENT_DRIVER', 'fake'), // fake | midtrans
        'qris_image' => 'images/qris.png',         // QRIS statis; admin memverifikasi manual di /admin/pesanan
        'midtrans' => [
            'server_key' => env('MIDTRANS_SERVER_KEY'),
            'is_production' => env('MIDTRANS_PRODUCTION', false),
            'method' => env('MIDTRANS_METHOD', 'qris'), // qris = QR tampil di halaman sendiri (jatuh ke snap bila channel belum aktif) | snap = halaman bayar Midtrans
            'expiry_minutes' => 60,
        ],
    ],
];
