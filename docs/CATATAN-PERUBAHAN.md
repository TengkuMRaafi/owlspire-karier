# Perubahan tahap A: hero dan roadmap

File yang berubah (timpa ke project):
- resources/views/home.blade.php  (hero baru + roadmap "Jalur Owlspire"; section lain tidak diubah)
- resources/css/app.css           (ditambah di bagian bawah)
- resources/js/app.js             (ditambah di bagian bawah)
- docs/PRD.md                     (baru)

Setelah menimpa file:
    npm install && npm run build   # hapus public/hot bila ada
    php artisan serve

Catatan:
- Ikon memakai SVG sprite di home.blade.php (compass, target, timer, book, unlock, briefcase, file, cap).
- Angka di mockup hero (78, 92) hanya contoh tampilan.
- Tombol langkah 6 menuju /lowongan, yang saat ini masih halaman wadah (tahap D).
- Foto hero: simpan sebagai public/images/hero.webp, otomatis dipakai.
