# Perubahan: isi materi jalur CPNS, BUMN, TNI/POLRI

Salin isi folder ini ke root proyek (timpa file yang sama), lalu jalankan:

    php artisan migrate
    php artisan view:clear

Migrasi `2026_11_27_100000_isi_materi_jalur_cpns_bumn_polri` mengisi 14 topik yang sudah ada
(penjelasan lengkap, istilah penting). Aman diulang dan tidak menimpa isi yang sudah disunting admin
(naikkan angka "v" di berkas JSON untuk memperbarui isi yang sudah terpasang).

## Isi
- database/seeders/data/belajar-mendalam/{cpns,bumn,tni-polri}.json : penjelasan lengkap tiap topik
- app/Services/Learning/JalurResmiSeeder.php : perbaikan format istilah (t/d) dan istilah untuk topik tanpa video
- resources/views/learning/lesson.blade.php : topik tanpa video menampilkan "Buka sumber resmi", bukan "Cari videonya di YouTube"
- data-no-unsaved pada form wawancara, CV, CV tools, pembayaran (form sementara/dikirim via JS) agar tidak salah memperingatkan
- tests/Feature/JalurResmiBelajarTest.php : 5 tes baru

## Memasang video resmi nanti
Admin > Belajar > "Pasang ke topik jalur": pilih topik, tempel ID video YouTube dari kanal resmi.
