# Roadmap

## Fase 1: Fondasi
- [ ] Jalankan setup, pastikan 6 halaman modul terbuka dan `php artisan test` hijau
- [ ] Auth (starter kit Livewire atau Fortify), role `admin`
- [ ] Livewire + Filament (cek kompatibilitas dengan Laravel 13 dulu)
- [ ] Desain sistem: komponen kartu, tombol, form; dark mode

## Fase 2: Simulasi
- [ ] Importer `legacy/exam-ci/simulasi-ujian/questions.js` ke tabel `questions`
- [ ] Alur attempt: mulai, simpan jawaban, selesai, `ExamGrader`
- [ ] Pembayaran asli: `MidtransGateway` (atau Xendit/Tripay) + webhook + `EntitlementService`
- [ ] Leaderboard dan pembahasan

## Fase 3: Lowongan
- [ ] Porting `legacy/jobs-python/sources.py` ke adapter
- [ ] Filter, simpan, pelacak lamaran (kanban), alert email/Telegram
- [ ] Parser CV dan skor kecocokan

## Fase 4: Learning
- [ ] Katalog kursus, bagian, materi (embed YouTube + ringkasan buatan sendiri)
- [ ] Progres, kuis per bab, sertifikat dengan halaman verifikasi publik

## Fase 5: Perekat dan monetisasi
- [ ] Skill gap -> jalur belajar otomatis (`CareerService`)
- [ ] Skor kesiapan karier, spaced repetition, streak
- [ ] Langganan, profil publik, talent pool
