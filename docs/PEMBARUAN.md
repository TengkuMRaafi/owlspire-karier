# Pembaruan: roadmap, karier, Premium QRIS, CV, belajar

## Menjalankan
    php artisan migrate --seed      # 15 karier, 405 soal karier, jalur belajar, paket Premium
    npm install && npm run build    # hapus public/hot bila ada
    php artisan test                # 42 tes

PHP 8.4+ wajib. Seeder aman diulang: data yang sudah diubah admin tidak ditimpa.

## Midtrans QRIS
.env: PAYMENT_DRIVER=midtrans, MIDTRANS_SERVER_KEY=..., MIDTRANS_PRODUCTION=false (sandbox) / true.
Dashboard Midtrans, Payment Notification URL: https://DOMAIN/api/webhooks/payment/midtrans
Halaman tagihan juga menanyakan status ke Midtrans tiap 4 detik, jadi tetap aktif walau webhook terlambat.
Harga paket: config/platform.php > premium.plans (contoh, ubah sesuai kebutuhan).

## Video belajar (Creative Commons)
.env: YOUTUBE_API_KEY=...   lalu   php artisan belajar:ambil-youtube [--karier=slug] [--per=2]
Hanya video berlisensi creativeCommon, embeddable, publik yang diterima. Tinjau di /admin/belajar.

## CV
DOCX/TXT/teks langsung bisa. PDF: composer require smalot/pdfparser  atau  apt install poppler-utils.

## Menambah soal / karier
/admin/karier menambah karier + simulasi kosong (3 segmen). Isi soal lewat Bank soal / Impor soal.
Soal gratis = is_trial true. Soal premium = is_trial false (dipakai lebih dulu di mode lanjutan).

## Modul baru / berubah
Careers (baru), Learning (pemutar, YouTube, progres), Billing (Midtrans QRIS, paket, OrderSettler),
Exam (kind selection|career), Admin (karier, video belajar), landing page, dashboard (skor kesiapan).
