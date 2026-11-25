# Merek Owlspire

**Nama**: Owlspire (jembatan kecil/jalan setapak). Metafora: menyeberang dari belajar menuju pekerjaan.
**Tagline**: Owlspire menuju kerja. Teks merek ada di satu tempat: `config/platform.php` -> `brand`.

## Logo
- `public/images/logo-mark.svg` (ikon), `public/images/logo.svg` (ikon + nama), `public/favicon.svg`.
- Di Blade pakai komponen `<x-logo />` (opsi: `:light="true"` untuk latar gelap, `:tagline="true"`, `size="lg"`).
- Bentuk: lengkung jembatan oranye di atas dek putih, dalam kotak grafit.

## Warna
Grafit `#1b2638` (header, band gelap) dan oranye `#3b6cd4` (tombol utama; teks putih di atasnya kontras 4,6:1). Token ada di `resources/css/app.css`.

## Font
- Judul: **Fraunces** (serif editorial) lewat `font-display`.
- Isi dan antarmuka: **Plus Jakarta Sans**.
- Dimuat dari Google Fonts di `resources/views/layouts/head.blade.php`. Untuk hosting sendiri, ganti dengan `@fontsource-variable/fraunces` dan `@fontsource/plus-jakarta-sans` (npm).

## Gambar hero
Bawaan: ilustrasi vektor `public/images/hero-owlspire.svg` (jembatan lengkung menanjak ke matahari terbit).
Ini ilustrasi buatan kode, bukan gambar AI. Untuk memakai gambar AI, hasilkan gambar lalu simpan sebagai
`public/images/hero.webp` (atau `hero.jpg`/`hero.png`). Landing dan halaman login otomatis memakainya; hapus berkasnya untuk kembali ke ilustrasi.

Saran: rasio 16:10, lebar min. 2400 px, WebP di bawah 400 KB. Biarkan sisi kiri atas relatif gelap/kosong karena teks hero ada di sana.

Prompt (Midjourney/Imagen/Flux):
> Cinematic wide shot at dusk, a long stone arched viaduct rising diagonally toward a glowing sunrise over layered misty mountain ridges, one small lone figure with a backpack walking upward on the bridge, warm amber and deep graphite palette, tiny lanterns along the bridge, volumetric haze, subtle film grain, calm hopeful mood, negative space on the upper left, no text, no logos, 16:10
