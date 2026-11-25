# Kode lama (hanya referensi untuk porting)

- `exam-ci/` : simulasi ujian CodeIgniter 4 + front-end JS. Soal ada di `simulasi-ujian/questions.js`
  (perlu dipindah ke tabel `questions` agar kunci jawaban tidak terbuka di browser).
- `jobs-python/` : agregator lowongan Flask. Sumber ada di `sources.py`, pencocokan bidang di `divisions.py`,
  parser CV di `cv_parser.py`.

Tidak disalin dengan sengaja: `.env`, `config.yaml`, `venv/`, log, dan gambar QRIS. Hapus folder ini kalau porting selesai.
