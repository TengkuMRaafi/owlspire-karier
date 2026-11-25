# Tabel per modul

| Modul | Tabel |
|---|---|
| Core | skills, settings |
| Accounts | users (+ kolom profil dan `preferences` JSON), user_skills |
| Learning | courses, course_sections, lessons, lesson_progress, course_enrollments, course_skill, certificates |
| Exam | exam_types, exam_segments, questions, exam_attempts, exam_attempt_answers, wrong_question_reviews |
| Jobs | job_sources, job_listings, job_skill, saved_jobs, job_applications, job_alerts, cv_profiles |
| Billing | plans, subscriptions, orders, payment_events, entitlements |

Definisi lengkap ada di `database/migrations`.

Catatan desain:
- `skills` dipakai bersama: lowongan, kursus, dan profil pengguna. Dari sini skill gap dihitung.
- `entitlements` adalah sumber kebenaran akses (langganan, tiket tryout, kursus berbayar).
- `payment_events` menyimpan payload webhook mentah untuk audit.
- Menambah dukungan institusi (B2B) nanti: tambah tabel `organizations` dan `organization_id` di tabel terkait.

## users.preferences (Pengaturan akun)

Kolom JSON nullable berisi preferensi per menu, bentuknya `{"exam": {"timer": "last5"}, "assistant": {...}}`.
Daftar kunci, pilihan, dan nilai bawaan ada di `config/preferences.php`; baca lewat `$user->pref('exam.timer')`.
Kunci yang tidak ada di skema atau bernilai tidak valid diabaikan dan jatuh ke nilai bawaan, jadi skema boleh berubah tanpa migrasi data.
