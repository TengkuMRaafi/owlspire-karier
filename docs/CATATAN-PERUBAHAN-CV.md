# Perubahan Analisis CV dan Perbaikan CV

Cara pasang: timpa file di project (struktur folder sama), lalu jalankan:

    php artisan view:clear
    npm run build

## Analisis CV (/cv)
- Hasil dipadatkan: 3 kartu kecil sebaris (Skill terbaca, Karier cocok, Kecocokan tertinggi), Pratinjau CV bertanda panah, dan Karier yang paling cocok.
- Kartu skor, perkembangan skor, daftar masalah, bagian terbaca, "Sudah bagus", daftar skill, dan kartu cocokkan lowongan dihapus dari tampilan.
- Pratinjau CV: isi CV tampil seperti kertas. Tiap bagian diberi catatan Bagus (hijau), Kurang menonjol (kuning), atau Perlu diperbaiki (merah), lengkap dengan panah. Bagian penting yang belum ada tampil sebagai kotak kosong bertanda. Baris ber-angka ditandai hijau, kalimat pasif dan data pribadi ditandai kuning.
- Saat tombol Analisis CV ditekan, kolom unggah/tempel disembunyikan dan diganti panel proses bertahap. Setelah hasil keluar, form tersimpan di tombol "Analisis CV baru".

## Perbaikan CV (AI) (/dashboard/cv-surat)
- Hasil Perbaiki CV memakai pratinjau bertanda panah yang sama: saran AI per bagian sesuai Posisi yang ingin dilamar (dan teks lowongan bila diisi), beserta contoh tulisan ulang yang bisa disalin.
- Ringkasan: penilaian kecocokan, kata kunci posisi yang belum ada, dan hal yang sebaiknya dihapus/diringkas.
- Saat Perbaiki CV atau Buat surat lamaran ditekan, form disembunyikan dan diganti panel proses sampai hasil keluar.
- Bila AI membalas dengan format yang tidak terbaca, hasil tetap tampil sebagai teks biasa seperti sebelumnya.

## Tampilan
- Laptop: kertas CV di kiri, catatan di kanan, panah mendatar dari catatan ke bagian CV.
- HP: tiap bagian CV diikuti catatannya tepat di bawah, panah kecil mengarah ke atas. Layout mengikuti lebar kartu (container query), jadi tetap rapi walau sidebar terbuka.

## File
app/Services/Careers/CvInsights.php, app/Services/Ai/CvWriter.php, app/Http/Controllers/CvController.php, app/Http/Controllers/Account/CvToolsController.php,
resources/views/careers/cv.blade.php, resources/views/careers/partials/cv-preview.blade.php (baru), resources/views/careers/partials/cv-loading.blade.php (baru),
resources/views/account/cvtools.blade.php, resources/css/pages/cv.css, resources/js/cv.js, tests/Feature/CvAnalysisV2Test.php

Catatan: rute dan logika "Cocokkan dengan lowongan" (POST /cv/cocokkan) tidak dihapus, hanya kartunya tidak ditampilkan lagi.
