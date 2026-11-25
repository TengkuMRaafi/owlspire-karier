# Struktur kode

Laravel MVC standar. Satu fitur = satu controller + model + view + (bila perlu) service.

```
app/
  Http/Controllers/         # Controller tipis: validasi, panggil service, return view
    Admin/                  # Controller panel admin
  Http/Middleware/          # EnsureAdmin (alias: admin)
  Models/                   # Model Eloquent + relasi + scope
  Services/<Domain>/        # Logika bisnis (Billing, Careers, Exam, Jobs, Learning)
  Jobs/                     # Job antrean/setelah-respons (RunJobSearch: pencarian lowongan)
  Contracts/                # Interface (PaymentGateway, JobSourceAdapter)
  Console/Commands/         # Perintah artisan
  Providers/AppServiceProvider.php   # Binding gateway dan JobAggregator
routes/  web.php  admin.php  api.php  console.php
resources/views/<fitur>/    # auth, dashboard, careers, exam, learning, jobs, billing, admin
resources/views/partials/   # Potongan bersama (hero landing, live-list: style, script, pager, dropdown)
database/migrations/        # Semua migrasi
database/seeders/           # Seeder + data/*.json
config/platform.php         # Pengaturan platform (tiket, Premium, gateway, sumber lowongan)
tests/Feature/
design.md                   # Desain, warna, komponen. Baca sebelum menambah fitur
```

## Alur

`routes` -> `Controller` -> `Service` -> `Model` -> `View`. Controller tidak berisi query rumit atau hitungan; pindahkan ke Service atau scope model.

## Nama view

Dot path mengikuti folder: `exam.index`, `learning.partials.results`, `admin.users.index`, `auth.login`.

## Lowongan

`JobsController` -> `RunJobSearch` -> `Services/Jobs/JobFinder` (sumber di `Sources/`, skor di `JobScorer`, bidang di `Divisions` + `resources/data/job-divisions.json`). Hasil disimpan di cache 6 jam. Baca CV otomatis khusus Premium.
Semua sumber lowongan (LinkedIn, Jobicy, RemoteOK, Arbeitnow) tanpa kunci API.
