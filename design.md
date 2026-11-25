# design.md

Baca file ini sebelum menambah fitur atau halaman. Tujuannya: tampilan dan struktur tetap konsisten.

## 1. Struktur kode (MVC)

| Yang ditambah | Taruh di |
|---|---|
| Halaman publik | `app/Http/Controllers/XController.php` + `resources/views/x/` + rute di `routes/web.php` |
| Halaman admin | `app/Http/Controllers/Admin/` + `resources/views/admin/` + rute di `routes/admin.php` |
| Webhook / API | `routes/api.php` (tanpa CSRF) |
| Tabel baru | `database/migrations/` + model di `app/Models/` |
| Logika bisnis | `app/Services/<Domain>/` (bukan di controller atau view) |
| Interface pihak ketiga | `app/Contracts/` + implementasi di `app/Services/<Domain>/` |
| Perintah artisan | `app/Console/Commands/` |
| Data awal | `database/seeders/` (JSON di `database/seeders/data/`) |
| Tes | `tests/Feature/` (satu file per fitur) |

Aturan:
- Controller tipis. Validasi lewat `$request->validate()` atau FormRequest, logika lewat Service.
- Nama view = folder fitur: `view('exam.index')`, partial di `resources/views/<fitur>/partials/`.
- Komponen bersama taruh di `resources/views/partials/` atau `resources/views/components/`.
- Komentar singkat, satu baris, jelaskan alasan bukan isi kode.
- Teks antarmuka dalam Bahasa Indonesia.

## 2. Warna (tema Owlspire: Biru + Netral + Kuning)

Jangan tulis hex baru. Pakai token di `resources/css/app.css`.

| Peran | Token | Hex |
|---|---|---|
| Header, sidebar, band gelap | `graphite-900` | #1b2638 |
| Teks utama | `ink` / `graphite-800` | #1b2638 / #1f2a3d |
| Teks sekunder | `graphite-600` / `500` | #4a5a73 / #667794 |
| Garis, border | `graphite-200` / `300` | #e2e6ec / #c5cfde |
| Latar halaman | `surface` | #f3f5f8 |
| Aksen utama (tombol, link aktif) | `accent-600` | #3b6cd4 |
| Aksen hover / teks aksen kecil | `accent-700` | #2f58b3 |
| Aksen di latar gelap | `accent-400` / `500` | #7fa8e8 / #4f82e6 |
| Latar lembut aksen | `accent-50` / `100` | #eef4fd / #dce8fb |

Aturan:
- Biru untuk tombol utama dan aksen. Kuning (#f5c542) hanya isian kecil (mata maskot, badge), jangan jadi warna teks.
- Teks putih hanya di atas `accent-600` atau lebih gelap (kontras sekitar 5:1).
- Di CSS inline pakai `var(--color-accent-600, #3b6cd4)` agar tetap ada cadangan.

## 3. Tipografi

- Judul besar: `font-display` (Poppins), berat 600-700, `letter-spacing` sedikit negatif.
- Isi dan antarmuka: Plus Jakarta Sans (default).
- Kata kunci di judul hero boleh `<em>` miring berwarna aksen.
- Eyebrow di atas judul: kelas `eyebrow` (terang) atau `eyebrow-dark`.

## 4. Komponen siap pakai (`resources/css/app.css`)

`btn`, `btn-primary`, `btn-dark`, `btn-outline`, `btn-outline-light`, `btn-danger`, `btn-sm`, `card`, `field`, `label`, `badge`, `eyebrow`.
Tombol utama satu per area. Aksi sekunder pakai `btn-outline`.

**Dropdown, tanggal, dan jam kustom:** jangan pakai `<select>`, `input[type=date]`, atau `input[type=time]` mentah di tampilan. Dropdown: `<x-dropdown>`. Tanggal dan jam: tulis `<input type="date|time" class="ui-input">` seperti biasa; `resources/js/picker.js` otomatis menggantinya dengan pemilih kustom (input asli jadi hidden, nilai tetap `YYYY-MM-DD` / `HH:MM`, `min`/`max`/`required` dihormati).

## 5. Pola halaman

Menu navbar: Simulasi (sudah berisi katalog Karier), Belajar, Lowongan. Premium bukan menu navbar; halamannya (`/harga`) dibuka lewat tombol ajakan. Katalog Karier lama (`/karier`) diarahkan ke `/simulasi#karier`.

Halaman menu utama punya hero sendiri dengan animasi dekoratif (`aria-hidden="true"`):

| Halaman | Hero | Awalan CSS |
|---|---|---|
| Simulasi (termasuk Karier) | Gelap, lembar jawab + jam | `sx-` |
| Belajar | Terang hangat, jalur 4 stasiun | `bl-` |
| Premium | Gelap-biru, kartu kaca | `pm-` |
| Lowongan | Terang, radar sumber (sudah terbuka) | `lw-` |
| Lowongan (Coming Soon) | Gelap, maskot + orbit ikon | `cs-` |
| Cari arah kariermu (tes + coba simulasi) | Kartu wizard + panel coba | `qz-`, `tr-` |
| Ajakan di landing | Band gelap + radar contoh | `dr-` |

Hero baru: pilih awalan CSS sendiri (2 huruf + `-`), jangan memakai ulang kelas halaman lain.

Daftar yang bisa dicari, difilter, dan dipaginasi (pola Belajar/Simulasi):
1. Controller baca `q`, filter, `page`; paginasi 3 item per halaman lewat `LengthAwarePaginator` dengan `fragment()`.
2. Bila header `X-Partial: results`, kembalikan hanya partial hasil.
3. View: `<form data-live-form>` + `<div data-live-results>@include('x.partials.results')</div>`.
4. Sertakan `partials.live-style` dan `partials.live-script`; pager `partials.live-pager`; dropdown `partials.live-dropdown`.
5. Keadaan kosong pakai `.lv-empty` dengan tombol `data-live-link data-reset`.

## 6. Animasi

- Wajib ada blok `@media (prefers-reduced-motion: reduce)` yang mematikan animasi.
- Durasi 0,2-0,4 dtk untuk interaksi, 0,5-0,7 dtk untuk masuk halaman, loop dekoratif 5-60 dtk.
- Hanya animasikan `transform` dan `opacity` (hindari width/height).
- Masuk saat scroll: atribut `data-reveal` (sudah dikelola `resources/js/app.js`).
- Animasi tidak boleh menyembunyikan konten penting bila JavaScript mati.

## 7. Aksesibilitas dan responsif

- Elemen dekoratif `aria-hidden="true"`; input punya `<label>` (boleh `sr-only`).
- Area klik minimal 40px; fokus terlihat (`:focus-visible`).
- Grid memakai `repeat(auto-fill, minmax(min(100%, Xrem), 1fr))`; uji lebar 360px.
- Gambar `loading="lazy"` dan `decoding="async"`, `alt=""` bila dekoratif.

## 8. Checklist fitur baru

1. Rute + controller + view + tes di `tests/Feature/`.
2. Warna dari token, tombol dari komponen.
3. Mobile 360px, reduced-motion, keyboard.
4. Daftar panjang: pakai pola live-list (bagian 5).
5. Perbarui `docs/` bila menambah tabel atau konfigurasi.

## 9. Aturan CSS (jangan menulis CSS di Blade)

- Blade hanya memanggil kelas. Tidak ada `<style>` di `resources/views/` (kecuali `welcome.blade.php` bawaan Laravel); semua gaya ada di `resources/css/` dan diimpor lewat `resources/css/app.css`.
- Struktur: `base/` (scrollbar, gerak halaman), `components/` (alert, badge, dropdown, empty, filter, form, pagination, stat, table), `layouts/panel.css` (shell admin dan akun), `pages/` (satu berkas per halaman/area).
- Komponen bersama berawalan `ui-` dan jadi satu-satunya sumber desain. Blade: `<x-alert>`, `<x-dropdown>`, `<x-icon>`, `<x-table-foot>`, `{{ $paginator->links() }}`.
- Panel (`pn-`) dipakai bersama admin dan akun. Menu diatur di `config/panel.php`; tidak ada tautan ke landing di dalam akun.
- Pembeda Gratis vs Premium berasal dari `config/platform.php > access`, dipakai di halaman Premium (bagian perbandingan di landing sudah dihapus).
- Awalan halaman: `ac-` (akun pengguna), `ad-` (admin).

## 10. Cari arah kariermu dan hak Premium

- Soal dan profil karier ada di `config/arah-karier.php` (dimensi, bobot pilihan, profil tiap karier, soal coba). Penilaian di `app/Services/Careers/CareerDirection.php`, bukan di browser.
- Gratis: mengerjakan tes dan coba simulasi, melihat satu karier teratas. Premium: skor dan pembahasan coba simulasi, tiga karier dengan alasan, profil dimensi, celah, langkah berikutnya. Data Premium tidak dikirim server ke non-Premium (jangan pindahkan pengecekan ke JavaScript).
- Karier baru tanpa profil di config otomatis memakai `tags` dari tabel `careers`.
- Menu Belajar menampilkan "Segera Hadir" (`resources/views/coming-soon.blade.php`, prefix `cs-`) selama `config('platform.learning_open')` false. Isi `LEARNING_OPEN=true` di `.env` untuk membukanya; saat tes (APP_ENV=testing) otomatis terbuka. Penahannya middleware `App\Http\Middleware\FeatureOpen` di rute `/belajar`.
- Tes arah karier (`/arah-karier`) hanya berisi tampilan karier. Coba simulasi punya halaman sendiri (`/simulasi/coba`, `TrialController`): soal saja, timer `arah-karier.trial_minutes` (5 menit), skor dan pembahasan khusus Premium.

## 11. Pengaturan akun dan preferensi

- Halaman `/dashboard/pengaturan` (`Account\SettingsController`, view `account/settings*`, CSS `pages/settings.css`, awalan `ac-set-`, `ac-opt`, `ac-seg`, JS `resources/js/settings.js`).
- Tata letak: sidebar bagian di kiri (pil mendatar di layar kecil), satu panel tampil sekaligus lewat `#hash` (mis. `/dashboard/pengaturan#dashboard`); tanpa JavaScript semua panel tetap tampil. Tiap preferensi satu baris `ac-opt`: teks di kiri, kontrol di kanan (saklar untuk `bool`, segmen `ac-seg` untuk 2-3 pilihan, `<x-dropdown>` untuk lebih dari itu). Jangan memakai `<select>` bawaan browser.
- Preferensi per menu didefinisikan di `config/preferences.php` (grup, label, pilihan, nilai bawaan). Form, validasi, dan simpan dibaca dari skema itu lewat `App\Services\Account\Preferences`; baca di kode dengan `$user->pref('grup.kunci')`.
- Tambah preferensi baru: tambah kunci di config lalu pakai di fitur yang dimaksud. Jangan menambah saklar yang belum dipakai fitur mana pun.
- Preferensi yang sudah terpasang: `exam.*` (ruang ujian), `learning.weekly_goal` (halaman Belajar), `assistant.*` (prompt Asisten AI), `notifications.*` (lonceng, `PanelComposer`), `dashboard.*` (bagian Dashboard), `privacy.leaderboard` (`ExamStats`).
- Tamu (tanpa akun) selalu memakai nilai bawaan, mis. di ruang ujian mode coba.

## 12. Peran akun dan akses

- Peran ada di kolom `users.role`: `admin` | `staff` | `member`. Daftar, izin per area panel, dan teks tabel "Peran dan akses" ada di `config/roles.php` (satu-satunya sumber).
- Admin: akses penuh, tanpa paket Gratis/Premium, peran terkunci (ditolak di UI dan oleh `User::booted`). Dibuat hanya lewat `php artisan owlspire:admin`.
- Staf: di bawah admin, dibuat admin di halaman Pengguna. Mengelola soal, karier, video belajar, dan hasil ujian; tidak membuka pesanan, pengguna, jenis ujian, dan info sistem.
- Pengguna (`member`): paket Gratis atau Premium mengikuti langganan aktif (`EntitlementService`), bukan role.
- Admin dan staf selalu lolos `EntitlementService::isPremium()` (akses penuh), tetapi tampilannya memakai label peran, bukan "Premium".
- Rute admin: grup `staff` (admin + staf), lalu `Route::middleware('area:nama')` per area. Menu sidebar admin memakai kunci `area` di `config/panel.php`.


## 13. Halaman detail admin dan preview akun

- Semua halaman detail admin (pengguna, pesanan, karier, hasil ujian, video, soal, jenis ujian) memakai pola yang sama: `<x-detail-hero>` (avatar/ikon/gambar + judul + chip + angka utama), baris `ui-stats is-compact` berisi `<x-detail-stat>`, lalu kartu `pn-card` dengan `<x-detail-head icon title>` dan kotak keterangan `<x-detail-item>` di dalam `<div class="ad-d-info">`.
- CSS-nya di `resources/css/pages/admin-detail.css`, awalan `ad-d-`. Warna hanya dari token; dekorasi (titik dan lingkaran) cukup di hero, jangan ditambah ke kartu lain.
- Alur berurutan (mis. status pesanan) memakai `ad-d-steps`; angka persen memakai `ad-d-ring`; komposisi memakai `ad-d-bar`. Pakai hanya bila isinya memang berurutan atau berbagi total.
- Preview akun di topbar (`layouts/panel.blade.php`, awalan `pn-prev-`) berisi sampul, avatar, nama, email, chip peran/paket, dan tanggal bergabung (`meJoined` dari `PanelComposer`), lalu menu Profil, Ke situs, Keluar.

## Performa animasi (HP)

Animasi yang bagus di desktop bisa berat di HP. Pegangan saat menambah gerak:

- Animasikan `transform` dan `opacity` pada elemen HTML. Hindari menganimasikan `width`, `height`, `background`, `box-shadow` (memicu layout/paint tiap frame).
- Jangan pasang `filter` (`drop-shadow`, `blur`) atau `backdrop-filter` pada elemen yang isinya bergerak. Untuk gelap-terang daratan SVG pakai `color-mix()` dan variabel CSS, bukan `filter: brightness()`.
- Animasi CSS pada elemen di dalam `<svg>` berjalan di thread utama dan menggambar ulang SVG tiap frame. Pakai seperlunya, dan jangan untuk hiasan yang bergerak terus.
- Loop `requestAnimationFrame`: jangan baca layout (`getPointAtLength`, `getBoundingClientRect`, `offsetHeight`) lalu tulis gaya di frame yang sama. Hitung tabel posisi sekali, lalu baca dari tabel. Tulis properti hanya bila nilainya berubah, dan hentikan loop saat bagian di luar layar.
- Bagian landing otomatis dijeda di luar layar (kelas `.is-off`, lihat `resources/js/app.js` dan `resources/css/base/lite.css`). Bagian baru yang punya animasi terus-menerus: tambahkan selektornya ke daftar di `app.js`.
- Aturan "mode ringan" untuk HP ada di `resources/css/base/lite.css`. Hiasan baru yang tidak membawa makna, matikan di sana.
- Logo dan ikon di navbar/footer diam (tanpa gerak).

## 11. Beli tanpa daftar (tiket dan Premium)

- Tamu cukup mengisi email. Setelah lunas, `EntitlementService::attachBuyer` membuat akun (`auto_password = true`) dengan sandi acak dari `PurchasePassword::generate()` (format 4-4-4).
- Sandi disimpan terenkripsi di `orders.meta.initial_password`, hanya tampil di halaman tagihan untuk pemilik akun yang sedang masuk (kartu `iv-cred`, tombol `data-copy`), dan dihapus otomatis saat sandi akun berubah (hook `User::booted`).
- Akun `auto_password` tidak diblokir verifikasi email (`User::skipsEmailVerification`), karena pembeli sudah memegang sandinya. Pemilik email asli tetap bisa mengambil alih lewat "Lupa kata sandi".
- Email yang sudah punya akun: akses diberikan ke akun itu, pembeli tidak otomatis masuk dan tidak melihat sandi apa pun.


## 14. Halaman error

- Semua kode HTTP yang terlihat pengguna memakai tampilan bermaskot, bukan halaman bawaan browser atau Laravel: `resources/views/errors/` (`403`, `404`, `413`, `419`, `429`, `503`, serta `4xx` dan `5xx` sebagai cadangan kode lain). Laravel memilih berkas dari kode status; permintaan JSON tetap menerima JSON.
- Kerangka: `errors/layout.blade.php`. Sengaja **berdiri sendiri** (tanpa sesi, database, `app.css`, `app.js`) karena halaman error harus tetap tampil saat aplikasi bermasalah. Jangan menambah `@auth`, query, atau komponen yang menyentuh database di folder ini.
- Gaya: `resources/css/errors.css` (entri Vite sendiri, awalan `er-`, adegan `e404-`, `e500-`, dst.). Adegan SVG ada di `errors/scenes/`. Warna tetap token di bagian 2; jangan tulis `<style>` di Blade.
- Tambah kode baru: buat `errors/<kode>.blade.php` yang `@extends('errors.layout')`, isi `title`, `code`, `heading`, `lead`, `scene`, `actions`. Tambah adegan di `scenes/` dengan kelas animasi berawalan kode itu. Wajib punya blok `prefers-reduced-motion` (sudah ditangani di `errors.css`).
- 429 membaca header `Retry-After` untuk hitung mundur. 503 (juga `maintenance.blade.php`) mengecek ulang otomatis tiap 30 detik lewat `data-er-poll`.
- Internet pengguna putus: banner `resources/js/offline.js` + `components/offline.css` (awalan `of-`), tampil di semua halaman yang memuat `app.js`.
- Gangguan sebelum Laravel (502/503/504 dari nginx, 413 dari upload): berkas statis `public/errors/502.html`, `503.html`, `504.html` dan `terlalu-besar.html`, dipasang lewat `error_page` di `deploy/nginx.conf.example`. Bila `errors.css` atau adegan berubah, buat ulang berkas-berkas itu agar tetap sama.
- Pratinjau lokal (`APP_ENV=local`): buka `/_error/404`, `/_error/429`, `/_error/503`, dst. Untuk 500 di lokal, rute itu tetap menampilkan halaman bermaskot (bukan halaman debug) karena memakai `abort()`.

## 15. Kunci jatah harian

- Jatah AI habis: bungkus bagian fiturnya dengan `<x-quota-lock>` (`resources/views/components/quota-lock.blade.php`, CSS `components/quota-lock.css`, awalan `ql-`, JS `resources/js/quota-lock.js`). Jangan membuat popup baru untuk kasus ini.
- Kunci menutup satu bagian halaman, bukan layar. Isi di belakangnya `inert`; server tetap yang menolak permintaan (HTTP 429 atau galat form), kunci hanya tampilan.
- Isi `keep` dengan hal yang masih bisa dibuka pengguna (riwayat, hasil lama). Waktu isi ulang dari `AiQuota::resetsAt()`.
- Rincian dan contoh pakai: `docs/CATATAN-PERUBAHAN-KUNCI-KUOTA.md`.

