# Perubahan hero: adegan "Lamar" dan kartu di HP

File yang berubah (timpa ke project):
- resources/views/partials/hero.blade.php  (pelamar baru, stempel DISETUJUI, kartu "Dilihat HRD" berganti jadi "Lamaran disetujui!")
- resources/css/pages/landing-hero.css     (alur 10 detik, blok HP baru)
- docs/CATATAN-PERUBAHAN-HERO-LAMAR.md     (catatan ini)

Setelah menimpa file:
    npm run build   # hapus public/hot bila ada

Isi perubahan:
- Pelamar digambar ulang (blazer, dasi, kaki, sepatu). Lengan kanan terangkat saat mengirim CV, lalu kedua lengan naik dan badan melompat kecil saat disetujui.
- CV terangkat dari tangan, melengkung dengan jejak putus-putus, mengecil masuk ke baris di monitor HRD.
- Urutan: dipindai, muncul 92%, centang hijau, stempel DISETUJUI, konfeti, HRD mengangguk, gelembung "Yes!" di atas kepala pelamar.
- HP: kartu melayang tidak lagi jadi deretan chip. Posisinya di sudut ilustrasi, sama seperti di laptop, ukurannya diperkecil.
- reduced-motion: stempel dan teks "Lamaran disetujui!" langsung tampil.

## Tambahan (perbaikan mata, gerak halus, laptop HP)
- Mata pelamar dan HRD tidak lagi melorot saat berkedip (transform-box .n-eye diset fill-box).
- Gerak pelamar: easing lembut, antisipasi sebelum lompat, squash saat mendarat, kepala miring mengikuti, lengan melambai kecil saat senang.
- Laptop di HP: layar dipadatkan jadi rasio laptop (tidak lagi mirip tablet), kartu "Cocok dengan skill kamu" digeser agar tidak menutupi soal.
