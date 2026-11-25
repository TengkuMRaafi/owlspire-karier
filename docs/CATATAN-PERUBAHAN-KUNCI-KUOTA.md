# Kunci jatah harian (Asisten AI, Wawancara AI, Analisis CV)

Saat jatah harian habis, bagian fitur yang bersangkutan dikunci dengan kartu buatan sendiri (bukan popup), lengkap dengan
hitung mundur ke isi ulang. Isi di belakang kunci redup dan tidak bisa disentuh (`inert`), jadi papan ketik dan pembaca layar
juga tidak bisa menembusnya. Server tetap menolak permintaan saat jatah habis; kunci hanya lapisan tampilan.

Setelah menimpa:

    npm run build   # hapus public/hot bila ada

Tidak ada migrasi dan tidak ada variabel .env baru.

## Berkas baru
- `resources/views/components/quota-lock.blade.php`  komponen `<x-quota-lock>`
- `resources/css/components/quota-lock.css`  gaya (awalan `ql-`), sudah diimpor di `app.css`
- `resources/js/quota-lock.js`  hitung mundur, kunci langsung tanpa muat ulang, buka sendiri saat jatah kembali (diimpor di `app.js`)
- `tests/Feature/QuotaLockTest.php`

## Berkas diubah
- `app/Services/Ai/AiQuota.php`  `AiQuota::resetsAt()`: saat jatah terisi lagi (pergantian hari, zona waktu aplikasi)
- `app/Http/Controllers/Account/ChatController.php`  respons 429 menyertakan `limit` dan `reset_at`
- `app/Http/Controllers/Account/InterviewController.php`  mengirim `premium` ke view
- `resources/views/account/chat.blade.php`  kartu chat dibungkus kunci; popup lama dihapus; kunci langsung saat pesan terakhir terpakai
- `resources/views/account/interview.blade.php`  kartu "Mulai wawancara baru" dibungkus kunci (riwayat dan wawancara berjalan tetap terbuka)
- `resources/views/careers/cv.blade.php`  formulir Analisis CV dibungkus kunci (hasil analisis terakhir tetap terbuka)
- `resources/css/pages/assistant.css`  gaya `cb-modal*` yang tidak terpakai dihapus

## Memakai di fitur lain
```blade
<x-quota-lock :locked="$remaining <= 0" :used="$limit - $remaining" :limit="$limit" :premium="$premium"
    title="Jatah ... habis" keep="Yang masih bisa dibuka...">
    ...bagian yang dikunci...
</x-quota-lock>
```
Kunci lewat JS tanpa muat ulang: `root.dispatchEvent(new CustomEvent('quota:lock', { detail: { used, limit } }))`.
Pratinjau tanpa menghabiskan jatah: isi `locked` dengan `true` sementara.

## Catatan
- Hitung mundur memakai jam server (selisih dengan jam perangkat dikoreksi); jam isi ulang ditampilkan menurut jam perangkat.
  Zona waktu aplikasi UTC, jadi isi ulang terjadi pukul 07.00 WIB.
- Menu "Perbaikan CV dan surat lamaran" sudah tidak punya rute, jadi `CvToolsController` dan `cvtools.blade.php` tidak diubah.
  Kuota `cv_rewrite` dan `cover_letter` juga tidak ada di `config/platform.php > ai_tools` (bawaannya 1 per hari untuk semua paket).
