# Perubahan: navbar & loading smooth, katalog Karier dengan search

File di zip ini sudah berstruktur sama dengan project. Timpa saja ke folder project.

Tidak perlu `npm run build`: CSS/JS baru ditulis inline (di layouts/motion, layouts/loader, dan halaman karier).
Cukup `php artisan view:clear` bila tampilan lama masih nyangkut.

## Yang berubah
- layouts/head, app, auth, dashboard, admin : memuat motion + loader.
- layouts/motion (baru)  : transisi halaman (View Transitions), gaya navbar, gaya layar loading.
- layouts/loader (baru)  : layar loading (sekali per sesi tab), garis progres atas, bayangan navbar saat scroll.
- Careers/Http/Controllers/CareerController : search, filter divisi, 3 per halaman.
- Careers/Services/CareerTheme (baru) : memilih tema gambar dari kata kunci (nama, divisi, tag, ringkasan).
- Careers/Resources/views/index + partials/results + partials/pagination.
- public/images/karier/*.svg (17 tema) : latar kartu bawaan.
- tests/Feature/CareerCatalogTest.php

## Mengganti gambar dengan foto asli
Taruh foto di public/images/karier/ dengan nama tema, mis. `it.jpg`, `uang.webp`, `mesin.jpg`.
Foto otomatis dipakai menggantikan SVG bawaan (urutan: jpg, jpeg, webp, png, baru svg).
Daftar tema: it, uang, mesin, k3, logistik, pendidikan, bisnis, desain, marketing, sdm, cs,
kesehatan, konstruksi, hukum, pertanian, kuliner, umum (cadangan).

## Menambah kata kunci / tema
Edit konstanta THEMES di CareerTheme.php. Kata <= 3 huruf dicocokkan utuh (it, ai, hr),
kata lebih panjang boleh berimbuhan ("akuntan" juga kena "akuntansi").

## Mengatur jumlah per halaman
CareerController::PER_PAGE (sekarang 3).
