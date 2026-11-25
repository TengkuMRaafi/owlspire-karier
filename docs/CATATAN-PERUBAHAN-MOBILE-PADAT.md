# Perubahan: tampilan HP yang padat (dashboard admin dan pengguna)

File yang berubah (timpa ke project):
- resources/css/base/mobile-compact.css  (baru)
- resources/css/app.css                  (satu baris: @import './base/mobile-compact.css' setelah responsive.css)

Setelah menimpa: npm run build

Isi:
- Kotak keterangan (.ad-d-info, contoh "Durasi penuh / Bagian / Soal di bank"): tile mini, 3 sebaris bila muat (4 di layar lebar), kalau tidak 2. Baris "wide" (nama) tetap penuh.
- Kartu angka (.ui-stats, .rp-stats), peran/paket/tile pengaturan admin, bagian skor, kartu daftar pengguna (.ac-cards), opsi paket, "kd-does", opsi tren: grid kiri-kanan dengan lebar minimum kecil.
- Alur langkah pesanan (.ad-d-steps): satu baris yang bisa digeser, tidak menumpuk ke bawah.
- Popover topbar (riwayat, notifikasi, akun): seukuran isi (maks 17,5rem), menempel di kanan bawah topbar, ada animasi masuk. Tidak lagi selebar layar.
- Judul, kartu panel, dan jarak antar-bagian dirapatkan.
- Desktop tidak berubah (semua aturan di bawah 640px).
Penyesuaian cepat: lebar minimum kolom ada di minmax(...) tiap aturan; kecilkan untuk lebih banyak kolom.
