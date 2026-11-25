<?php

// Bank soal "Cari arah kariermu" dan soal coba simulasi. Kunci jawaban hanya dibaca server.
return [
    // Dimensi yang diukur: kode => [label, penjelasan singkat, saran bila masih rendah]
    'dimensions' => [
        'analitis' => ['Analitis', 'Membedah data, pola, dan sebab-akibat', 'latih membaca data dan menarik kesimpulan dari angka'],
        'teknis' => ['Teknis', 'Membangun dan memperbaiki sistem', 'mulai dari proyek kecil yang berjalan, bukan hanya teori'],
        'kreatif' => ['Kreatif', 'Menciptakan ide dan rancangan baru', 'biasakan membuat banyak alternatif sebelum memilih satu'],
        'sosial' => ['Sosial', 'Bekerja dengan dan lewat orang lain', 'latih mendengarkan dan merangkum kebutuhan orang lain'],
        'lapangan' => ['Lapangan', 'Praktik langsung, standar, dan keselamatan', 'cari kesempatan melihat proses kerja nyata secara langsung'],
        'bisnis' => ['Bisnis', 'Target, negosiasi, dan peluang', 'pelajari cara kerja biaya, harga, dan keuntungan'],
        'ketelitian' => ['Ketelitian', 'Kerapian, kepatuhan, dan akurasi detail', 'bangun kebiasaan checklist dan pemeriksaan ulang'],
    ],

    // Profil tuntutan tiap karier (slug => dimensi => bobot 0..1). Karier baru tanpa profil memakai tag dari tabel careers.
    'profiles' => [
        'ai-engineer' => ['teknis' => 1, 'analitis' => .9, 'kreatif' => .3, 'ketelitian' => .3],
        'soc-analyst' => ['teknis' => 1, 'analitis' => .8, 'ketelitian' => .7, 'lapangan' => .2],
        'ehs-specialist' => ['lapangan' => 1, 'ketelitian' => .9, 'sosial' => .5, 'analitis' => .4],
        'procurement' => ['bisnis' => 1, 'analitis' => .6, 'ketelitian' => .6, 'sosial' => .5],
        'industrial-engineer' => ['analitis' => .9, 'lapangan' => .8, 'teknis' => .6, 'ketelitian' => .5],
        'learning-specialist' => ['sosial' => 1, 'kreatif' => .7, 'analitis' => .3, 'ketelitian' => .3],
        'logistik' => ['lapangan' => 1, 'bisnis' => .6, 'ketelitian' => .6, 'analitis' => .4],
        'partnership' => ['bisnis' => 1, 'sosial' => .9, 'kreatif' => .3],
        'data-analyst' => ['analitis' => 1, 'bisnis' => .6, 'teknis' => .5, 'ketelitian' => .5],
        'software-developer' => ['teknis' => 1, 'analitis' => .7, 'kreatif' => .4, 'ketelitian' => .4],
        'ui-ux' => ['kreatif' => 1, 'sosial' => .6, 'analitis' => .4, 'teknis' => .3],
        'digital-marketing' => ['kreatif' => .9, 'bisnis' => .8, 'analitis' => .5, 'sosial' => .3],
        'akuntansi' => ['ketelitian' => 1, 'analitis' => .8, 'bisnis' => .5],
        'hr-talent' => ['sosial' => 1, 'bisnis' => .5, 'ketelitian' => .4, 'analitis' => .3],
        'customer-service' => ['sosial' => 1, 'ketelitian' => .5, 'bisnis' => .3, 'lapangan' => .2],
    ],

    // Pertanyaan berbasis situasi. Tiap pilihan: [teks, bobot dimensi, ciri yang terbaca dari pilihan itu]
    'questions' => [
        [
            'topic' => 'Selisih angka antar laporan',
            'text' => 'Dasbor penjualan menunjukkan kenaikan 12% bulan ini, tetapi laporan keuangan menulis 7%. Direksi rapat pukul 09.00 besok dan meminta satu angka yang bisa dipertanggungjawabkan. Langkah pertamamu:',
            'options' => [
                ['Membandingkan definisi kedua angka: apakah retur, pembatalan, pajak, dan batas tanggal tutup buku diperlakukan sama', ['analitis' => 2, 'ketelitian' => 1], 'membedah definisi di balik sebuah angka'],
                ['Menulis kueri rekonsiliasi yang mencocokkan transaksi dari kedua sumber dan menandai selisihnya otomatis', ['teknis' => 2, 'analitis' => 1], 'membangun alat agar selisih bisa dilacak ulang'],
                ['Mengambil sampel transaksi di hari-hari dengan selisih terbesar dan mencocokkannya dokumen demi dokumen', ['ketelitian' => 2, 'lapangan' => 1], 'memeriksa bukti satu per satu sampai klop'],
                ['Mempertemukan pemilik data penjualan dan keuangan malam ini untuk menyepakati satu definisi resmi', ['sosial' => 2, 'bisnis' => 1], 'menuntaskan masalah angka lewat kesepakatan antar orang'],
            ],
        ],
        [
            'topic' => 'Layanan lambat berpola',
            'text' => 'Aplikasi internal melambat tepat sekitar pukul 10.00 setiap hari kerja, lalu pulih sendiri sebelum tim sempat memeriksa. Tidak ada rilis baru dalam dua minggu. Kamu paling condong untuk...',
            'options' => [
                ['Memasang pencatatan metrik dan profiling kueri pada jam itu agar penyebabnya terlihat langsung', ['teknis' => 2, 'analitis' => 1], 'menelusuri masalah sistem lewat log dan metrik'],
                ['Mengorelasikan jadwal tugas terjadwal, lonjakan lalu lintas, dan kegiatan tim lain pada jam yang sama', ['analitis' => 2, 'teknis' => 1], 'mencari pola waktu di balik gejala'],
                ['Menanyakan ke tiap tim apa yang mereka jalankan pukul 10.00, termasuk laporan manual dan impor data', ['sosial' => 2, 'lapangan' => 1], 'menggali informasi dari orang yang memakai sistemnya'],
                ['Menulis langkah reproduksi dan hipotesis tertulis lebih dulu, baru mengubah satu hal pada satu waktu', ['ketelitian' => 2, 'analitis' => 1], 'mengubah satu variabel dan mendokumentasikannya'],
            ],
        ],
        [
            'topic' => 'Alur pendaftaran yang ditinggalkan',
            'text' => 'Empat dari sepuluh calon pengguna berhenti di langkah ketiga proses pendaftaran produkmu. Anggaran riset hampir habis dan tim ingin keputusan dalam seminggu. Kamu akan...',
            'options' => [
                ['Membuat tiga alternatif alur dengan prototipe kasar dan mengujinya ke beberapa orang dalam dua hari', ['kreatif' => 2, 'sosial' => 1], 'mencoba beberapa rancangan lewat prototipe cepat'],
                ['Memecah data tahap demi tahap berdasarkan perangkat, sumber trafik, dan waktu untuk melihat siapa yang paling sering berhenti', ['analitis' => 2, 'teknis' => 1], 'membedah data corong sebelum merancang ulang'],
                ['Menelepon lima orang yang berhenti dan meminta mereka menceritakan apa yang mereka pikirkan di langkah itu', ['sosial' => 2, 'kreatif' => 1], 'mendengar langsung alasan pengguna'],
                ['Menghitung nilai tiap satu persen pemulihan dibanding biaya perbaikannya, lalu memilih yang rasionya terbaik', ['bisnis' => 2, 'analitis' => 1], 'menimbang perbaikan dari nilai bisnisnya'],
            ],
        ],
        [
            'topic' => 'Pelanggaran keselamatan di lapangan',
            'text' => 'Kamu melihat dua pekerja membongkar perancah di ketinggian tanpa sabuk pengaman. Mandor berkata, "Tinggal sepuluh menit, kejar target hari ini." Kamu...',
            'options' => [
                ['Menghentikan pekerjaan saat itu juga, memastikan sabuk terpasang, baru melanjutkan', ['lapangan' => 2, 'ketelitian' => 1], 'bertindak tegas di tempat demi keselamatan'],
                ['Menarik mandor ke samping, menanyakan kenapa sabuk tidak dipakai, dan mencari solusi agar tidak mengejar target dengan cara ini', ['sosial' => 2, 'lapangan' => 1], 'mencari akar perilaku lewat percakapan'],
                ['Mencatat temuan lengkap dengan foto, waktu, dan lokasi, lalu melaporkannya sesuai prosedur insiden', ['ketelitian' => 2, 'analitis' => 1], 'mendokumentasikan temuan agar bisa ditindaklanjuti'],
                ['Memeriksa catatan insiden bulan-bulan lalu untuk melihat apakah pelanggaran serupa berulang pada tim atau shift tertentu', ['analitis' => 2, 'lapangan' => 1], 'mencari pola pelanggaran dari data historis'],
            ],
        ],
        [
            'topic' => 'Kenaikan harga dari pemasok',
            'text' => 'Pemasok utama menaikkan harga 9% dengan alasan biaya bahan baku, berlaku bulan depan. Kontrak berjalan masih empat bulan lagi. Responsmu:',
            'options' => [
                ['Meminta rincian komponen biaya dan menawarkan kontrak volume lebih panjang dengan harga yang dikunci', ['bisnis' => 2, 'sosial' => 1], 'bernegosiasi dengan menawarkan sesuatu yang bernilai bagi kedua pihak'],
                ['Membandingkan indeks harga bahan baku dengan kenaikan yang diminta untuk melihat apakah 9% wajar', ['analitis' => 2, 'bisnis' => 1], 'menguji klaim pihak lain dengan data pasar'],
                ['Membaca klausul eskalasi dan perubahan harga pada kontrak untuk memastikan apakah kenaikan itu sah sebelum bernegosiasi', ['ketelitian' => 2, 'bisnis' => 1], 'memeriksa dasar hukum sebelum berunding'],
                ['Menghubungi pemasok lama dan dua alternatif baru untuk mengetahui posisi tawar yang sebenarnya', ['bisnis' => 2, 'lapangan' => 1], 'mengumpulkan alternatif untuk memperkuat posisi tawar'],
            ],
        ],
        [
            'topic' => 'Menjelaskan hal rumit ke direksi',
            'text' => 'Kamu harus menjelaskan hasil analisis yang rumit kepada direksi non-teknis dalam sepuluh menit, dan mereka harus mengambil keputusan hari itu juga. Pendekatanmu:',
            'options' => [
                ['Memakai analogi sehari-hari dan satu visual besar yang menggantikan sebagian besar slide', ['kreatif' => 2, 'sosial' => 1], 'mengemas hal rumit menjadi gambaran yang mudah ditangkap'],
                ['Menanyakan lebih dulu keputusan apa yang mereka hadapi, lalu hanya menyiapkan yang mempengaruhi keputusan itu', ['sosial' => 2, 'bisnis' => 1], 'berangkat dari kebutuhan pendengar'],
                ['Memilih satu angka kunci beserta tingkat ketidakpastiannya, dan menaruh sisanya di lampiran', ['analitis' => 2, 'ketelitian' => 1], 'meringkas ke angka yang paling menentukan'],
                ['Menyiapkan demo langsung yang memperlihatkan dampak keputusan ke proses mereka', ['teknis' => 2, 'kreatif' => 1], 'menunjukkan lewat demonstrasi, bukan hanya penjelasan'],
            ],
        ],
        [
            'topic' => 'Tiga insiden sekaligus',
            'text' => 'Dalam satu jam masuk tiga masalah: pelanggan besar tidak bisa transaksi, data sebuah laporan tampak salah, dan satu rekan mengaku salah mengirim berkas internal ke pihak luar. Kamu hanya sendirian. Urutan yang paling alami bagimu:',
            'options' => [
                ['Memulihkan transaksi pelanggan besar dulu karena dampak finansial dan reputasinya paling cepat membesar', ['bisnis' => 2, 'teknis' => 1], 'memilih yang dampaknya paling cepat membesar'],
                ['Menangani berkas yang bocor dulu karena risikonya hukum dan tidak bisa ditarik kembali, lalu sisanya', ['ketelitian' => 2, 'lapangan' => 1], 'mendahulukan risiko yang tidak bisa dibatalkan'],
                ['Membagi tugas ke orang yang tepat dalam lima menit pertama, lalu memantau ketiganya sambil menangani yang paling kritis', ['sosial' => 2, 'bisnis' => 1], 'mengoordinasi orang saat banyak hal terjadi bersamaan'],
                ['Membuat daftar singkat dampak, tenggat, dan ketergantungan ketiganya sebelum memutuskan apa pun', ['analitis' => 2, 'ketelitian' => 1], 'menilai dulu sebelum memutuskan di bawah tekanan'],
            ],
        ],
        [
            'topic' => 'Menguasai hal baru dengan cepat',
            'text' => 'Dalam dua minggu kamu harus mampu mengelola alat atau topik yang sama sekali baru bagimu, dan hasilnya akan dipakai tim. Cara belajarmu:',
            'options' => [
                ['Langsung membuat proyek kecil yang berjalan, lalu membaca dokumentasi setiap kali macet', ['teknis' => 2, 'kreatif' => 1], 'belajar sambil membangun sesuatu yang nyata'],
                ['Mencari orang yang sudah ahli, menanyakan kesalahan umum pemula, dan mengamati cara kerjanya', ['sosial' => 2, 'lapangan' => 1], 'belajar dari orang yang sudah berpengalaman'],
                ['Menyusun peta konsep dulu: istilah kunci, hubungan antar bagian, dan hal yang paling sering salah dipahami', ['analitis' => 2, 'ketelitian' => 1], 'memahami struktur sebelum mempraktikkan'],
                ['Mengerjakan studi kasus nyata dari pekerjaan sendiri dan membandingkan hasilnya dengan contoh standar', ['lapangan' => 2, 'ketelitian' => 1], 'menguji pemahaman pada kasus pekerjaan nyata'],
            ],
        ],
        [
            'topic' => 'Permintaan klien yang kabur',
            'text' => 'Klien berkata, "Pokoknya buat yang kelihatan modern dan bikin penjualan naik." Tidak ada angka target, tidak ada contoh, dan tenggat tiga minggu. Kamu...',
            'options' => [
                ['Menyusun tiga arah visual berbeda dan menunjukkannya sebagai bahan diskusi, bukan hasil akhir', ['kreatif' => 2, 'sosial' => 1], 'memperjelas permintaan lewat contoh konkret'],
                ['Meminta data penjualan saat ini dan menanyakan definisi "naik": berapa persen, dalam berapa lama, dari kanal mana', ['bisnis' => 2, 'analitis' => 1], 'mengubah harapan kabur menjadi target terukur'],
                ['Menuliskan ruang lingkup, asumsi, dan hal yang tidak termasuk, lalu meminta persetujuan tertulis sebelum bekerja', ['ketelitian' => 2, 'bisnis' => 1], 'mengunci ruang lingkup tertulis sejak awal'],
                ['Mengadakan sesi singkat dengan klien dan penggunanya untuk memahami apa yang sebenarnya mereka anggap modern', ['sosial' => 2, 'kreatif' => 1], 'menggali makna di balik kata-kata klien'],
            ],
        ],
        [
            'topic' => 'Menemukan kesalahan kecil di dokumen besar',
            'text' => 'Kamu memegang laporan tiga ratus baris yang akan ditandatangani pimpinan besok. Dari sekilas membaca, ada dua baris yang jumlahnya terasa janggal. Kamu...',
            'options' => [
                ['Menghitung ulang seluruh kolom dengan rumus pemeriksa, lalu menelusuri semua baris yang tidak cocok', ['ketelitian' => 2, 'teknis' => 1], 'memeriksa ulang seluruh dokumen secara sistematis'],
                ['Mencari penyebab di sumber data: apakah dua baris itu mewakili pola yang bisa saja ada di baris lain', ['analitis' => 2, 'ketelitian' => 1], 'melacak apakah kesalahan itu bagian dari pola'],
                ['Menanyakan langsung ke penginput data dan mencocokkannya dengan dokumen fisik bila perlu', ['lapangan' => 2, 'sosial' => 1], 'mengecek ke sumber asli dan orang yang menginput'],
                ['Memberi tahu pimpinan bahwa ada selisih, menawarkan dua pilihan: tunda tanda tangan atau tanda tangan dengan catatan', ['bisnis' => 2, 'sosial' => 1], 'menyajikan pilihan keputusan lengkap dengan risikonya'],
            ],
        ],
        [
            'topic' => 'Dua rekan berseberangan',
            'text' => 'Dua rekan senior berselisih tajam soal cara menyelesaikan proyek bersama, dan kamu tahu keduanya sama-sama punya alasan kuat. Proyek ini juga milikmu. Kamu...',
            'options' => [
                ['Mengajak keduanya duduk bersama, menggali tujuan masing-masing, dan mencari titik temu yang mencapai keduanya', ['sosial' => 2, 'bisnis' => 1], 'menengahi dengan mencari tujuan bersama'],
                ['Membuat kriteria penilaian yang disepakati lebih dulu (biaya, waktu, risiko), lalu menilai kedua cara dengan kriteria itu', ['analitis' => 2, 'sosial' => 1], 'mengganti perdebatan dengan kriteria yang bisa diuji'],
                ['Membuat prototipe kecil dari kedua cara agar perbedaannya terlihat nyata dan bukan sekadar pendapat', ['teknis' => 2, 'kreatif' => 1], 'memutuskan lewat bukti dari percobaan kecil'],
                ['Merujuk standar atau prosedur yang berlaku di perusahaan sebagai patokan netral', ['ketelitian' => 2, 'lapangan' => 1], 'menjadikan standar sebagai patokan netral'],
            ],
        ],
        [
            'topic' => 'Hasil uji yang tidak meyakinkan',
            'text' => 'Kamu menjalankan uji coba dua versi halaman selama dua minggu. Versi B lebih baik 3%, tetapi perbedaannya tidak signifikan secara statistik. Atasan ingin tetap memakai B. Kamu...',
            'options' => [
                ['Menjelaskan bahwa selisih sekecil itu bisa kebetulan, lalu menghitung berapa lama uji harus diperpanjang agar valid', ['analitis' => 2, 'ketelitian' => 1], 'menolak menyimpulkan lebih dari yang dibuktikan data'],
                ['Menimbang bahwa biaya beralih ke B hampir nol, sehingga risikonya kecil walau buktinya lemah', ['bisnis' => 2, 'analitis' => 1], 'menimbang risiko keputusan, bukan hanya angka'],
                ['Memeriksa apakah ada gangguan pada uji, seperti pembagian trafik yang tidak merata atau kampanye yang berjalan bersamaan', ['teknis' => 2, 'ketelitian' => 1], 'memeriksa kualitas pelaksanaan uji lebih dulu'],
                ['Mengajak beberapa pengguna menjelaskan perbedaan rasanya, untuk mengetahui apa yang sebenarnya mereka rasakan', ['sosial' => 2, 'kreatif' => 1], 'melengkapi angka dengan cerita pengguna'],
            ],
        ],
        [
            'topic' => 'Bottleneck di proses',
            'text' => 'Di gudang, pesanan selalu menumpuk di tahap pengemasan sementara tahap lain sering menganggur. Tim sudah menambah orang di pengemasan dua kali tanpa banyak perubahan. Kamu...',
            'options' => [
                ['Mengukur waktu tiap tahap dan antrean di antaranya untuk menemukan hambatan yang sebenarnya', ['analitis' => 2, 'lapangan' => 1], 'mengukur proses sebelum menambah sumber daya'],
                ['Berdiri di lantai kerja beberapa jam, mengamati dan berbicara dengan petugas tentang apa yang menghambat mereka', ['lapangan' => 2, 'sosial' => 1], 'memahami masalah dengan melihat prosesnya langsung'],
                ['Merancang ulang tata letak dan alur barang agar jarak tempuh dan penantian berkurang', ['kreatif' => 2, 'lapangan' => 1], 'merancang ulang alur kerja, bukan menambah orang'],
                ['Membuat sistem penjadwalan yang mengatur pesanan masuk sesuai kapasitas tiap tahap', ['teknis' => 2, 'analitis' => 1], 'mengatur aliran kerja dengan sistem'],
            ],
        ],
        [
            'topic' => 'Peringatan keamanan dini hari',
            'text' => 'Pukul 03.00 sistem memberi peringatan: akun seorang manajer login dari negara lain, lalu mengunduh ratusan berkas dalam sepuluh menit. Manajer itu sedang cuti. Kamu...',
            'options' => [
                ['Segera mengunci akun dan memutus sesi aktif, lalu menelusuri log untuk melihat apa saja yang diakses', ['teknis' => 2, 'lapangan' => 1], 'bertindak cepat membatasi kerusakan lalu menyelidiki'],
                ['Mengumpulkan log login, alamat IP, dan perangkat untuk memastikan ini serangan sungguhan atau perjalanan yang sah', ['analitis' => 2, 'teknis' => 1], 'memverifikasi dugaan sebelum bertindak besar'],
                ['Mengikuti prosedur penanganan insiden: catat waktu, bukti, dan siapa yang diberi tahu, sebelum mengubah apa pun', ['ketelitian' => 2, 'lapangan' => 1], 'mengikuti prosedur agar bukti tetap utuh'],
                ['Menghubungi manajer dan atasannya untuk memastikan kondisi sebenarnya sambil memberi tahu tim hukum', ['sosial' => 2, 'ketelitian' => 1], 'mengonfirmasi lewat orang yang bersangkutan'],
            ],
        ],
        [
            'topic' => 'Melatih orang baru',
            'text' => 'Seorang rekan baru harus mampu bekerja mandiri dalam satu bulan pada tugas yang kamu kuasai, tetapi kamu hanya punya dua jam seminggu untuk mendampingi. Kamu...',
            'options' => [
                ['Menyusun urutan latihan dari yang mudah ke yang sulit, tiap tahap dengan contoh dan kriteria selesai yang jelas', ['kreatif' => 2, 'ketelitian' => 1], 'merancang jalur belajar bertahap'],
                ['Mendampingi saat mengerjakan tugas nyata, lalu mengajukan pertanyaan agar ia menemukan jawabannya sendiri', ['sosial' => 2, 'lapangan' => 1], 'mengajar lewat pertanyaan pada tugas nyata'],
                ['Merekam proses kerja dan menulis panduan ringkas yang bisa dipakai ulang oleh orang berikutnya', ['teknis' => 2, 'ketelitian' => 1], 'mengubah pengetahuan menjadi panduan yang bisa dipakai ulang'],
                ['Menentukan ukuran keberhasilan bulan pertama dan memeriksanya tiap minggu agar jelas siapa yang perlu bantuan apa', ['analitis' => 2, 'bisnis' => 1], 'mengukur kemajuan dengan indikator yang jelas'],
            ],
        ],
        [
            'topic' => 'Anggaran dipotong',
            'text' => 'Anggaran proyekmu dipotong 30% di tengah jalan, sementara cakupan dan tenggat tidak berubah menurut atasanmu. Kamu...',
            'options' => [
                ['Menghitung ulang biaya tiap bagian, memetakan mana yang paling berdampak, dan menyajikan opsi pengurangan cakupan', ['bisnis' => 2, 'analitis' => 1], 'menyusun opsi dengan hitung-hitungan biaya dan dampak'],
                ['Mencari cara mengerjakan hal yang sama dengan sumber daya lebih hemat, misalnya otomatisasi atau alat gratis', ['teknis' => 2, 'kreatif' => 1], 'mencari cara hemat lewat alat dan otomatisasi'],
                ['Menemui pemangku kepentingan satu per satu untuk menyepakati bagian mana yang boleh dikurangi', ['sosial' => 2, 'bisnis' => 1], 'membangun kesepakatan sebelum memotong'],
                ['Memeriksa kewajiban kontrak dan standar mutu yang tidak boleh dikurangi sebelum memangkas apa pun', ['ketelitian' => 2, 'lapangan' => 1], 'memastikan batas yang tidak boleh dilanggar'],
            ],
        ],
        [
            'topic' => 'Pelanggan marah yang bereskalasi',
            'text' => 'Pelanggan korporat mengancam berhenti berlangganan setelah gangguan layanan dua kali dalam sebulan. Ia meminta berbicara dengan pimpinan, padahal kasusnya ada di tanganmu. Kamu...',
            'options' => [
                ['Mendengarkan sampai selesai, merangkum keluhannya dengan kata-katamu sendiri, dan memastikan ia merasa dipahami sebelum menawarkan solusi', ['sosial' => 2, 'ketelitian' => 1], 'meredakan emosi dengan mendengar dan merangkum'],
                ['Menyiapkan rangkuman penyebab, perbaikan yang sudah dilakukan, dan kompensasi yang bisa ditawarkan untuk dibawa ke pimpinan', ['bisnis' => 2, 'sosial' => 1], 'menyiapkan paket solusi untuk mempertahankan pelanggan'],
                ['Menelusuri riwayat gangguan dan mencocokkan waktunya dengan catatan teknis agar penjelasan yang diberikan akurat', ['analitis' => 2, 'ketelitian' => 1], 'memberi penjelasan berbasis catatan yang akurat'],
                ['Bekerja sama dengan tim teknis agar penyebab gangguan diperbaiki permanen, bukan sekadar dimaafkan', ['teknis' => 2, 'sosial' => 1], 'mendorong perbaikan permanen di akar masalah'],
            ],
        ],
        [
            'topic' => 'Laporan yang tidak dibaca',
            'text' => 'Laporan mingguan yang kamu susun dikirim ke dua puluh orang, tetapi hanya tiga yang membukanya. Atasanmu ingin laporan itu tetap ada. Kamu...',
            'options' => [
                ['Merombak tampilannya menjadi satu halaman dengan ringkasan visual dan hanya tiga hal yang perlu ditindaklanjuti', ['kreatif' => 2, 'bisnis' => 1], 'mendesain ulang agar pesan utamanya terbaca'],
                ['Menanyakan ke beberapa penerima keputusan apa yang mereka butuhkan dari laporan itu dan apa yang bisa dibuang', ['sosial' => 2, 'analitis' => 1], 'bertanya ke pembaca apa yang sebenarnya berguna'],
                ['Mengotomatiskan penyusunan dan hanya mengirim peringatan saat ada angka yang melewati ambang batas', ['teknis' => 2, 'analitis' => 1], 'mengganti laporan rutin dengan peringatan otomatis'],
                ['Memeriksa tiap angka dan istilah di laporan agar konsisten dengan definisi resmi, supaya penerima percaya', ['ketelitian' => 2, 'bisnis' => 1], 'membangun kepercayaan lewat konsistensi dan akurasi'],
            ],
        ],
        [
            'topic' => 'Aturan baru yang mulai berlaku',
            'text' => 'Peraturan baru mengubah cara pencatatan dan pelaporan yang kamu tangani, berlaku dua bulan lagi. Sebagian rekan menganggapnya tidak terlalu penting. Kamu...',
            'options' => [
                ['Membaca teksnya sendiri, menandai pasal yang mengubah pekerjaan kita, dan membuat daftar pemeriksaan kepatuhan', ['ketelitian' => 2, 'analitis' => 1], 'membaca aturan langsung dan menerjemahkannya ke daftar periksa'],
                ['Mengukur dampaknya ke proses, biaya, dan sanksi bila terlambat untuk menentukan prioritas persiapan', ['analitis' => 2, 'bisnis' => 1], 'menimbang dampak dan sanksi sebelum menyiapkan diri'],
                ['Mengadakan sesi ringkas untuk rekan agar mereka paham apa yang berubah dan alasannya', ['sosial' => 2, 'ketelitian' => 1], 'menjelaskan perubahan ke rekan agar mereka mau menyesuaikan'],
                ['Mengubah formulir, alur, atau sistem sekarang agar pelaksanaan aturan baru otomatis terbawa', ['teknis' => 2, 'lapangan' => 1], 'menanamkan kepatuhan langsung ke alur kerja'],
            ],
        ],
        [
            'topic' => 'Kesalahanmu sendiri setelah rilis',
            'text' => 'Dua hari setelah hasil kerjamu dipakai, kamu menemukan kesalahan yang kamu buat sendiri. Dampaknya kecil sejauh ini, tetapi belum ada yang sadar. Kamu...',
            'options' => [
                ['Segera melapor ke atasan, menjelaskan dampaknya, dan membawa usulan perbaikan beserta cara mencegah ulangannya', ['ketelitian' => 2, 'sosial' => 1], 'terbuka soal kesalahan dan membawa solusi'],
                ['Memperbaiki dulu secepatnya, lalu menghitung berapa pihak yang terdampak untuk disampaikan secara terukur', ['teknis' => 2, 'analitis' => 1], 'memperbaiki dan mengukur dampak dengan cepat'],
                ['Menelusuri bagaimana kesalahan itu lolos dari pemeriksaan, lalu menambah tahap pemeriksaan agar kasus serupa tertangkap', ['analitis' => 2, 'ketelitian' => 1], 'memperbaiki proses yang meloloskan kesalahan'],
                ['Menghubungi langsung orang yang paling terdampak dan menjelaskan apa yang terjadi sebelum mereka mengetahuinya dari pihak lain', ['sosial' => 2, 'lapangan' => 1], 'memberi tahu orang terdampak secara langsung'],
            ],
        ],
    ],

    // Bidang untuk radar hasil simulasi (urutan = urutan sumbu radar).
    'trial_areas' => [
        'keuangan' => 'Keuangan',
        'operasional' => 'Operasional',
        'proyek' => 'Proyek & Layanan',
        'data' => 'Data & Teknologi',
        'keselamatan' => 'Keselamatan',
        'bisnis' => 'Bisnis & SDM',
    ],

    // Soal coba simulasi: 15 soal situasi kerja yang detail, dikerjakan dalam 'trial_minutes' menit. 'answer' = indeks pilihan benar (mulai 0).
    'trial_minutes' => 5,
    'trial' => [
        [
            'text' => 'Sebuah pabrik memiliki mesin press dengan kebisingan terukur 95 dB di posisi operator. Sesuai hierarki pengendalian risiko K3, tindakan manakah yang berada di tingkat PALING efektif?',
            'options' => ['Mewajibkan operator memakai penyumbat telinga selama bekerja', 'Membatasi waktu operator di dekat mesin dengan rotasi tiap dua jam', 'Mengganti mesin dengan model yang tingkat kebisingannya di bawah 85 dB', 'Memasang rambu area bising dan pelatihan tahunan'],
            'answer' => 2,
            'explain' => 'Urutan efektivitas: eliminasi, substitusi, rekayasa teknik, administratif, APD. Mengganti mesin adalah substitusi, lebih tinggi dari rotasi (administratif) dan penyumbat telinga (APD).',
            'area' => 'keselamatan',
        ],
        [
            'text' => 'Perusahaan menerima pembayaran di muka Rp120.000.000 pada 1 Oktober untuk layanan 12 bulan yang dimulai 1 Oktober. Berapa pendapatan yang boleh diakui sampai laporan 31 Desember (basis akrual)?',
            'options' => ['Rp120.000.000', 'Rp30.000.000', 'Rp10.000.000', 'Rp90.000.000'],
            'answer' => 1,
            'explain' => 'Layanan berjalan 3 dari 12 bulan: 120 juta ÷ 12 × 3 = 30 juta. Sisa Rp90 juta tercatat sebagai pendapatan diterima di muka (liabilitas).',
            'area' => 'keuangan',
        ],
        [
            'text' => 'Pesanan pembelian (PO) 100 unit @Rp50.000. Gudang mencatat penerimaan 90 unit sesuai spesifikasi, tetapi faktur pemasok menagih 100 unit. Menurut prinsip three-way match, tindakan yang benar adalah...',
            'options' => ['Menahan faktur, meminta faktur koreksi, dan hanya membayar 90 unit (Rp4.500.000) setelah sesuai', 'Membayar penuh Rp5.000.000 lalu menagih kelebihannya nanti', 'Membayar Rp5.000.000 karena PO menyebut 100 unit', 'Menolak seluruh faktur dan membatalkan PO'],
            'answer' => 0,
            'explain' => 'PO, dokumen penerimaan barang, dan faktur harus cocok. Pembayaran hanya untuk yang diterima; selisih diselesaikan lewat faktur koreksi atau pengiriman susulan.',
            'area' => 'operasional',
        ],
        [
            'text' => 'Proyek: A (3 hari) harus selesai dulu. Setelah A, B (4 hari) dan C (6 hari) berjalan paralel. D (2 hari) baru mulai setelah B dan C selesai. Berapa durasi minimal proyek dan berapa float (kelonggaran) aktivitas B?',
            'options' => ['9 hari; float B 0 hari', '13 hari; float B 0 hari', '11 hari; float B 6 hari', '11 hari; float B 2 hari'],
            'answer' => 3,
            'explain' => 'Jalur kritis A-C-D = 3 + 6 + 2 = 11 hari. B hanya butuh 4 hari padahal C 6 hari, sehingga B boleh terlambat 2 hari tanpa menunda proyek.',
            'area' => 'proyek',
        ],
        [
            'text' => 'Tabel pelanggan berisi 1.000 baris dan tabel pesanan berisi riwayat transaksi. Kamu perlu daftar pelanggan yang BELUM PERNAH memesan. Pendekatan SQL yang benar adalah...',
            'options' => ['INNER JOIN pelanggan dengan pesanan lalu ambil yang kolom pesanan.id-nya NULL', 'LEFT JOIN pelanggan ke pesanan lalu filter WHERE pesanan.id IS NULL', 'LEFT JOIN pelanggan ke pesanan lalu filter WHERE pesanan.id = 0', 'RIGHT JOIN pesanan ke pelanggan lalu hitung COUNT(*)'],
            'answer' => 1,
            'explain' => 'LEFT JOIN mempertahankan semua pelanggan; yang tidak punya pesanan memiliki kolom pesanan bernilai NULL. INNER JOIN membuang justru baris yang dicari.',
            'area' => 'data',
        ],
        [
            'text' => 'Biaya akuisisi pelanggan (CAC) Rp150.000. Rata-rata pesanan Rp80.000 dengan margin kotor 40%, dan pelanggan rata-rata memesan 6 kali selama berlangganan. Berapa LTV berbasis margin kotor dan apa kesimpulannya?',
            'options' => ['Rp480.000; rasio LTV/CAC 3,2 sehingga sangat sehat', 'Rp320.000; rasio 2,1 sehingga cukup sehat', 'Rp192.000; rasio 1,28 sehingga tipis dan jauh di bawah patokan umum 3', 'Rp80.000; rasio 0,53 sehingga rugi besar'],
            'answer' => 2,
            'explain' => 'LTV margin kotor = 80.000 × 40% × 6 = 192.000; 192.000 ÷ 150.000 = 1,28. Memakai omzet (480.000) menyesatkan karena mengabaikan biaya barang.',
            'area' => 'bisnis',
        ],
        [
            'text' => 'Permintaan harian rata-rata 200 unit, lead time pemasok 5 hari, dan stok pengaman 150 unit. Pada level stok berapa pemesanan ulang (reorder point) harus dipicu?',
            'options' => ['1.000 unit', '350 unit', '1.350 unit', '1.150 unit'],
            'answer' => 3,
            'explain' => 'Reorder point = permintaan selama lead time + stok pengaman = 200 × 5 + 150 = 1.150 unit.',
            'area' => 'operasional',
        ],
        [
            'text' => 'Awal tahun perusahaan memiliki 200 karyawan, akhir tahun 180, dan sepanjang tahun 30 orang keluar. Berapa tingkat turnover tahunan menurut rumus standar (keluar ÷ rata-rata headcount)?',
            'options' => ['15,0%', '15,8%', '16,7%', '10,0%'],
            'answer' => 1,
            'explain' => 'Rata-rata headcount = (200 + 180) ÷ 2 = 190. Turnover = 30 ÷ 190 ≈ 15,8%.',
            'area' => 'bisnis',
        ],
        [
            'text' => 'SLA layanan: penyelesaian maksimal 8 jam kerja. Jam kerja Senin sampai Jumat pukul 08.00 sampai 17.00. Tiket masuk Jumat pukul 16.30. Kapan batas penyelesaiannya?',
            'options' => ['Senin pukul 15.30', 'Senin pukul 16.30', 'Sabtu pukul 00.30', 'Senin pukul 08.30'],
            'answer' => 0,
            'explain' => 'Jumat terpakai 30 menit (16.30 sampai 17.00). Sisa 7,5 jam kerja dihitung Senin dari 08.00, sehingga batasnya Senin 15.30.',
            'area' => 'proyek',
        ],
        [
            'text' => 'Peserta magang perlu membaca laporan penjualan selama tiga bulan. Menurut prinsip least privilege, pemberian akses mana yang paling tepat?',
            'options' => ['Peran editor di seluruh folder departemen agar tidak perlu meminta lagi', 'Akun admin sementara yang dihapus setelah magang selesai', 'Peran hanya-baca pada folder laporan tersebut, dengan masa berlaku otomatis berakhir di akhir magang', 'Berbagi kata sandi akun tim penjualan'],
            'answer' => 2,
            'explain' => 'Least privilege: hak akses minimum yang dibutuhkan, untuk waktu sesingkat yang diperlukan. Berbagi kata sandi juga menghilangkan jejak audit.',
            'area' => 'keselamatan',
        ],
        [
            'text' => 'Mesin memiliki availability 90%, performance 80%, dan quality 95%. Berapa OEE (Overall Equipment Effectiveness)-nya?',
            'options' => ['88,3%', '68,4%', '72,0%', '76,0%'],
            'answer' => 1,
            'explain' => 'OEE = 0,90 × 0,80 × 0,95 = 0,684 atau 68,4%. Faktornya dikalikan, bukan dirata-rata.',
            'area' => 'operasional',
        ],
        [
            'text' => 'Biaya tetap sebuah usaha Rp60.000.000 per bulan. Harga jual Rp50.000 per unit dan biaya variabel Rp30.000 per unit. Berapa unit yang harus terjual per bulan untuk mencapai titik impas?',
            'options' => ['1.200 unit', '2.000 unit', '6.000 unit', '3.000 unit'],
            'answer' => 3,
            'explain' => 'Margin kontribusi = 50.000 − 30.000 = 20.000 per unit. Titik impas = 60.000.000 ÷ 20.000 = 3.000 unit.',
            'area' => 'keuangan',
        ],
        [
            'text' => 'Dalam uji kegunaan dengan 5 peserta, 4 orang gagal menemukan tombol "Ajukan klaim" yang berada di menu pojok kanan atas. Langkah yang paling tepat adalah...',
            'options' => ['Memindahkan tombol ke tempat yang lebih menonjol atau memberi label lebih jelas, lalu menguji ulang dengan peserta baru', 'Menambah halaman panduan agar pengguna belajar menemukannya', 'Mengabaikannya karena sampel hanya 5 orang', 'Menyimpulkan bahwa para peserta kurang teliti'],
            'answer' => 0,
            'explain' => 'Masalah yang dialami 4 dari 5 orang hampir pasti nyata. Perbaiki penemuannya, lalu uji ulang untuk memastikan perbaikannya berhasil.',
            'area' => 'proyek',
        ],
        [
            'text' => 'Aplikasi memanggil API pembayaran, tetapi koneksi putus sebelum respons diterima, sehingga aplikasi mengirim ulang permintaan. Cara teknis agar pelanggan tidak tertagih dua kali adalah...',
            'options' => ['Menambah waktu tunggu (timeout) menjadi 5 menit', 'Mencatat log setiap permintaan agar bisa dicek manual', 'Mengirim kunci idempotensi (idempotency key) unik per transaksi sehingga permintaan ulang dikenali sebagai transaksi yang sama', 'Membatasi pelanggan hanya satu transaksi per hari'],
            'answer' => 2,
            'explain' => 'Kunci idempotensi membuat server mengenali permintaan ulang yang sama dan mengembalikan hasil sebelumnya, bukan memproses transaksi baru.',
            'area' => 'data',
        ],
        [
            'text' => 'Kontrak proyek bernilai Rp2.000.000.000 mengatur denda keterlambatan 0,1% per hari dari nilai kontrak. Proyek terlambat 12 hari kalender. Berapa dendanya?',
            'options' => ['Rp2.400.000', 'Rp12.000.000', 'Rp240.000.000', 'Rp24.000.000'],
            'answer' => 3,
            'explain' => '0,1% × 2.000.000.000 = 2.000.000 per hari. Kali 12 hari = Rp24.000.000.',
            'area' => 'keuangan',
        ],
    ],
];
