# PRD: Owlspire, landing dan alur pengguna

## 1. Latar belakang
Owlspire sudah punya karier, simulasi, jalur belajar, analisis CV, dan Premium QRIS. Modul Lowongan masih kosong. Landing page terlalu banyak teks dan belum menjelaskan alur. Pengunjung non-IT sulit menangkap bahwa ini tempat simulasi ujian sekaligus pencari kerja.

## 2. Tujuan
- Pengunjung baru paham fungsi situs dalam 5 detik: latihan ujian, belajar, cari kerja.
- Alur pemakaian terlihat jelas lewat roadmap animasi dan tutorial bergambar.
- Teks dikurangi, diganti ikon, ilustrasi, dan animasi.

## 3. Target pengguna
Pencari kerja dan peserta seleksi (CPNS, BUMN, Polri/TNI), lulusan baru, dan orang non-IT yang membuka situs dari HP.

## 4. Ruang lingkup
Masuk: hero baru, roadmap "Jalur Owlspire", tutorial 4 panel dan demo 3 soal, pemadatan section, halaman Lowongan (data contoh dulu), tur dashboard, empty state bergambar.
Tidak masuk (tahap ini): sumber lowongan asli (adapter), talent pool, B2B, aplikasi mobile.

## 5. Kebutuhan fungsional
| ID | Kebutuhan | Tahap |
|---|---|---|
| F1 | Hero: judul maksimal 8 kata, 3 chip ikon (Simulasi, Belajar, Lowongan), 2 tombol, mockup produk dengan kartu melayang dan angka naik | A (selesai) |
| F2 | Roadmap 6 langkah: jalur tergambar mengikuti scroll, tokoh berjalan, langit berubah, stasiun menyala, konfeti di akhir | A (selesai) |
| F3 | Stasiun bisa diklik atau fokus keyboard untuk melompat; kartu aksi per langkah dengan tombol tujuan | A (selesai) |
| F4 | Roadmap vertikal di layar sempit (kurang dari 700 px) | A (selesai) |
| F5 | Ikon SVG, tanpa emoji | A (selesai) |
| F6 | Tutorial 4 panel beranimasi (Pilih, Kerjakan, Hasil, Lamar) dan demo 3 soal tanpa akun | B |
| F7 | Pemadatan section lain menjadi baris ikon; FAQ 4 pertanyaan | C |
| F8 | Halaman Lowongan: kartu dengan persen kecocokan, filter, simpan, papan pelacak lamaran | D |
| F9 | Tur 3 langkah di dashboard dan empty state bergambar | E |
| F10 | Foto hero realistis (A: dihasilkan AI, B: foto stok berlisensi) | Menunggu keputusan |

## 6. Kebutuhan non-fungsional
- Mobile first; roadmap dan hero nyaman di HP.
- Hormati `prefers-reduced-motion`; semua animasi punya versi statis.
- Aksesibel: fokus keyboard terlihat, kontras sesuai merek (oranye #C2530F dengan teks putih 4,6:1), label ARIA di roadmap.
- Ringan: tanpa pustaka animasi tambahan; gambar WebP di bawah 400 KB, lazy load.
- Konsisten dengan merek di `docs/BRAND.md`.

## 7. Metrik keberhasilan
- Klik tombol "Coba gratis" dari landing naik.
- Persentase pengunjung yang scroll sampai langkah 6 di roadmap.
- Pengunjung yang memulai simulasi tanpa akun.
- Rasio mendaftar setelah simulasi gratis.
- Waktu sampai klik pertama menurun.

## 8. Risiko
- Animasi scroll berat di HP lama: pakai transform dan `requestAnimationFrame`-ringan, uji di perangkat murah.
- Lowongan masih contoh: beri label jelas "contoh tampilan" agar tidak menyesatkan.
- Foto AI: periksa hak pakai dan hindari wajah yang menyerupai orang nyata.

## 9. Pertanyaan terbuka
- Foto hero: AI atau foto stok?
- Maskot: tetap tokoh bertas generik atau dibuat karakter khusus Owlspire?
- Sumber lowongan pertama yang akan dihubungkan.
