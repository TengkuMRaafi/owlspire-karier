<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;

/**
 * Jalur belajar non-karier: CPNS, BUMN, TNI/POLRI. Aman diulang (firstOrCreate), isi yang sudah disunting admin tidak ditimpa.
 * Video hanya yang kepemilikannya terverifikasi lewat situs resmi institusi; sisanya tautan portal resmi.
 */
class JalurResmiSeeder
{
    private const BKN = ['https://cat.bkn.go.id/simulasi', 'Portal resmi BKN'];

    public static function apply(): int
    {
        $n = 0;
        foreach (self::data() as $slug => $c) {
            $course = Course::firstOrCreate(['slug' => $slug], [
                'title' => $c['title'], 'summary' => $c['summary'], 'description' => $c['description'],
                'level' => 'pemula', 'category' => $c['category'], 'is_published' => true, 'is_premium' => false, 'career_id' => null,
            ]);
            $section = CourseSection::firstOrCreate(['course_id' => $course->id, 'title' => 'Jalur inti'], ['position' => 1]);

            foreach ($c['topics'] as $i => $t) {
                $video = $t['video'] ?? null;
                $url = $video ? 'https://www.youtube.com/watch?v='.$video[0] : $t['url'];
                $lesson = Lesson::firstOrCreate(['section_id' => $section->id, 'slug' => 'topik-'.($i + 1)], [
                    'title' => $t['title'], 'position' => $i + 1, 'is_free_preview' => $i < 2, 'is_approved' => true,
                    'type' => $video ? 'video' : 'artikel', 'provider' => $video ? 'youtube' : 'link',
                    'video_id' => $video[0] ?? null, 'video_title' => $video[1] ?? null,
                    'content_url' => $url, 'source_url' => $t['url'],
                    'credit_name' => $t['source'], 'license' => $video ? 'Disematkan lewat pemutar resmi YouTube' : 'Tautan sumber resmi',
                    'body' => $t['overview'],
                    'details' => ['points' => $t['points'], 'terms' => array_map(fn ($x) => ['t' => $x[0], 'd' => $x[1]], $t['terms'] ?? []), 'practice' => $t['practice']],
                ]);
                $n += $lesson->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $n + self::fillTerms() + self::normalizeTerms();
    }

    /** Istilah lama tersimpan sebagai pasangan [istilah, arti]; halaman topik membaca kunci t dan d. */
    public static function normalizeTerms(): int
    {
        $n = 0;
        $courseIds = Course::whereIn('slug', array_keys(self::data()))->pluck('id');
        $sectionIds = CourseSection::whereIn('course_id', $courseIds)->pluck('id');
        foreach (Lesson::whereIn('section_id', $sectionIds)->get() as $lesson) {
            $details = $lesson->details ?? [];
            $terms = $details['terms'] ?? [];
            if (! $terms || ! array_is_list($terms) || isset($terms[0]['t'])) {
                continue;
            }
            $details['terms'] = array_map(fn ($x) => isset($x['t']) ? $x : ['t' => $x[0] ?? '', 'd' => $x[1] ?? ''], $terms);
            $lesson->update(['details' => $details]);
            $n++;
        }

        return $n;
    }

    /** Istilah penting untuk topik yang belum punya; yang sudah diisi (atau disunting admin) tidak diubah. */
    public static function fillTerms(): int
    {
        $n = 0;
        foreach (self::terms() as $key => $pairs) {
            [$slug, $topik] = explode('/', $key);
            $course = Course::where('slug', $slug)->first();
            $section = $course ? CourseSection::where('course_id', $course->id)->where('title', 'Jalur inti')->first() : null;
            $lesson = $section ? Lesson::where('section_id', $section->id)->where('slug', $topik)->first() : null;
            if (! $lesson || ! empty($lesson->details['terms'] ?? [])) {
                continue;
            }
            $details = $lesson->details ?? [];
            $details['terms'] = array_map(fn ($x) => ['t' => $x[0], 'd' => $x[1]], $pairs);
            $lesson->update(['details' => $details]);
            $n++;
        }

        return $n;
    }

    private static function terms(): array
    {
        return [
            'jalur-cpns/topik-3' => [['Silogisme', 'Penarikan kesimpulan dari dua premis'], ['Deret', 'Barisan angka dengan pola tertentu'], ['Analogi', 'Kesamaan hubungan antara dua pasangan kata']],
            'jalur-cpns/topik-4' => [['BerAKHLAK', 'Nilai dasar ASN: Berorientasi pelayanan, Akuntabel, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif'], ['Profesionalisme', 'Bekerja disiplin, bertanggung jawab, dan taat aturan']],
            'jalur-cpns/topik-5' => [['Passing grade', 'Nilai ambang batas minimal tiap subtes SKD'], ['SSCASN', 'Portal resmi pendaftaran CASN milik BKN'], ['SKB', 'Seleksi Kompetensi Bidang']],
            'jalur-bumn/topik-1' => [['FHCI', 'Forum Human Capital Indonesia, penyelenggara Rekrutmen Bersama BUMN'], ['RBB', 'Rekrutmen Bersama BUMN']],
            'jalur-bumn/topik-2' => [['TKD', 'Tes Kemampuan Dasar: verbal, numerik, dan logika'], ['Modus tollens', 'Jika p maka q, q tidak terjadi, maka p tidak terjadi']],
            'jalur-bumn/topik-4' => [['Learning agility', 'Kemampuan belajar cepat dari pengalaman dan menerapkannya di situasi baru'], ['Conditional sentence', 'Kalimat pengandaian berpola if']],
            'jalur-tni-polri/topik-1' => [['Pantukhir', 'Penentuan kelulusan akhir'], ['Akpol', 'Akademi Kepolisian'], ['Akmil', 'Akademi Militer']],
            'jalur-tni-polri/topik-2' => [['Psikotes', 'Rangkaian tes psikologi: kecerdasan, kecermatan, kepribadian'], ['Analogi', 'Kesamaan hubungan antara dua pasangan kata']],
            'jalur-tni-polri/topik-3' => [['Kecermatan', 'Kemampuan teliti, fokus, dan cepat dalam memeriksa data'], ['Kata baku', 'Kata yang sesuai kaidah ejaan dan KBBI']],
            'jalur-tni-polri/topik-4' => [['Kesamaptaan', 'Kebugaran jasmani: kekuatan, daya tahan, dan kelincahan'], ['Shuttle run', 'Lari bolak-balik jarak pendek untuk menilai kelincahan']],
            'jalur-tni-polri/topik-5' => [['Tripel Pythagoras', 'Tiga bilangan bulat yang memenuhi a kuadrat + b kuadrat = c kuadrat, misalnya 3-4-5'], ['Wawasan kebangsaan', 'Pemahaman tentang dasar negara dan bangsa']],
        ];
    }

    private static function data(): array
    {
        return [
            'jalur-cpns' => [
                'title' => 'Jalur belajar: CPNS', 'category' => 'CPNS',
                'summary' => 'Lima topik berurutan untuk menyiapkan SKD CPNS: format CAT, TWK, TIU, TKP, dan strategi passing grade.',
                'description' => "Jalur ini menyiapkan kamu untuk Seleksi Kompetensi Dasar (SKD) CPNS. SKD memuat 110 soal dalam 100 menit: 30 soal TWK, 35 soal TIU, dan 45 soal TKP.\n\nKamu mulai dari cara kerja CAT BKN, lalu mendalami tiap subtes, dan ditutup dengan strategi menembus ambang batas. Selesai satu topik, langsung uji di Simulasi CPNS.",
                'topics' => [
                    ['title' => 'Cara kerja CAT BKN dan format SKD', 'url' => 'https://cat.bkn.go.id', 'source' => 'BKN (cat.bkn.go.id)', 'video' => ['tp2l-4pjeto', 'Lihat bagaimana CAT bekerja'],
                        'overview' => 'Kenali sistem Computer Assisted Test (CAT) BKN sebelum belajar materi: cara memilih jawaban, berpindah nomor, dan membaca sisa waktu. Format SKD: 110 soal, 100 menit (TWK 30, TIU 35, TKP 45).',
                        'points' => ['SKD = TWK + TIU + TKP, total 110 soal dalam 100 menit', 'Skor TWK dan TIU: benar 5, salah 0. Skor TKP: tiap opsi bernilai 1 sampai 5', 'Latih di simulasi resmi BKN agar tidak kaget dengan antarmukanya'],
                        'terms' => [['CAT', 'Computer Assisted Test, tes berbasis komputer'], ['Passing grade', 'Nilai ambang minimal per subtes']],
                        'practice' => 'Buka simulasi resmi cat.bkn.go.id/simulasi, kerjakan satu sesi penuh dan catat subtes mana yang paling menghabiskan waktu.'],
                    ['title' => 'TWK: Tes Wawasan Kebangsaan', 'url' => 'https://www.mpr.go.id', 'source' => 'MPR RI', 'video' => ['r4JzNI5Po_c', 'PANCASILA'],
                        'overview' => 'TWK menguji pemahaman Pancasila, UUD NRI 1945, NKRI, Bhinneka Tunggal Ika, nasionalisme, bela negara, dan sejarah perjuangan bangsa.',
                        'points' => ['Empat pilar: Pancasila, UUD NRI 1945, NKRI, Bhinneka Tunggal Ika', 'Hafal urutan dan makna sila, serta pasal kunci UUD 1945', 'Pahami integritas, bela negara, bahasa Indonesia, dan pilar negara'],
                        'terms' => [['NKRI', 'Negara Kesatuan Republik Indonesia'], ['Bela negara', 'Sikap dan perilaku warga membela kelangsungan negara']],
                        'practice' => 'Buat tabel 5 sila: bunyi, makna, dan satu contoh penerapan di lingkungan kerja instansi.'],
                    ['title' => 'TIU: Tes Intelegensia Umum', 'url' => self::BKN[0], 'source' => self::BKN[1],
                        'overview' => 'TIU mengukur kemampuan verbal (sinonim, antonim, analogi), numerik (hitung, deret, perbandingan), dan figural/logika (pola gambar dan penalaran).',
                        'points' => ['Kuasai perkalian, pecahan, persen, dan deret angka', 'Verbal: perbanyak kosakata baku dan analogi', 'Logika: silogisme dan penalaran analitis, hindari menebak panjang'],
                        'practice' => 'Kerjakan 35 soal TIU dengan timer 35 menit, lalu tandai soal yang kamu lewati dan hitung penyebabnya.'],
                    ['title' => 'TKP: Tes Karakteristik Pribadi', 'url' => self::BKN[0], 'source' => self::BKN[1],
                        'overview' => 'TKP menilai sikap kerja ideal ASN: pelayanan publik, jejaring kerja, sosial budaya, teknologi informasi dan komunikasi, profesionalisme, dan anti radikalisme.',
                        'points' => ['Tidak ada jawaban salah: tiap opsi bernilai 1 sampai 5', 'Pilih opsi yang proaktif, melayani, taat aturan, dan solutif', 'Hindari opsi pasif, menunda, atau melanggar prosedur'],
                        'practice' => 'Ambil 10 soal TKP, tulis alasan opsi nilai 5 vs nilai 1 untuk tiap soal.'],
                    ['title' => 'Strategi passing grade dan alur SSCASN', 'url' => 'https://sscasn.bkn.go.id', 'source' => 'BKN (SSCASN)',
                        'overview' => 'Lolos SKD butuh memenuhi ambang batas tiap subtes, bukan hanya total. Sebagai acuan, ambang batas CPNS 2024: TWK 65, TIU 80, TKP 166 (data 2024; cek pengumuman resmi tahun berjalan).',
                        'points' => ['Ambang batas berlaku per subtes: gagal satu berarti gugur', 'Amankan TKP dulu, lalu TWK, lalu TIU yang butuh waktu hitung', 'Buat akun SSCASN lebih awal dan siapkan dokumen berkas', 'Informasi resmi hanya dari sscasn.bkn.go.id'],
                        'practice' => 'Hitung skor simulasimu per subtes dan bandingkan dengan ambang batas 2024.'],
                ],
            ],
            'jalur-bumn' => [
                'title' => 'Jalur belajar: BUMN', 'category' => 'BUMN',
                'summary' => 'Empat topik untuk rekrutmen BUMN: alur seleksi, TKD, AKHLAK, dan Bahasa Inggris serta Learning Agility.',
                'description' => "Jalur ini menyiapkan kamu untuk Rekrutmen Bersama BUMN. Tahap 1 berisi Tes Kemampuan Dasar (TKD), AKHLAK, dan Wawasan Kebangsaan; tahap 2 berisi Bahasa Inggris dan Learning Agility.\n\nPelajari alurnya, kuasai nilai AKHLAK, lalu uji di Simulasi BUMN.",
                'topics' => [
                    ['title' => 'Alur seleksi Rekrutmen Bersama BUMN', 'url' => 'https://rekrutmenbersama.fhcibumn.id', 'source' => 'FHCI BUMN',
                        'overview' => 'Rekrutmen Bersama BUMN berjalan bertahap: seleksi administrasi, tes tahap 1, tes tahap 2, lalu wawancara di perusahaan tujuan. Semua informasi resmi ada di portal FHCI BUMN.',
                        'points' => ['Tahap 1: TKD, AKHLAK, Wawasan Kebangsaan', 'Tahap 2: Bahasa Inggris dan Learning Agility', 'Pantau jadwal hanya dari rekrutmenbersama.fhcibumn.id'],
                        'practice' => 'Buat akun di portal, lengkapi profil, dan siapkan scan dokumen sebelum pendaftaran dibuka.'],
                    ['title' => 'TKD dan Wawasan Kebangsaan', 'url' => 'https://rekrutmenbersama.fhcibumn.id', 'source' => 'FHCI BUMN',
                        'overview' => 'TKD mencakup kemampuan verbal, numerik, dan logika, mirip TIU. Wawasan Kebangsaan menguji Pancasila, UUD 1945, dan pemahaman kebangsaan.',
                        'points' => ['Latih deret, persen, dan penalaran logis dengan waktu ketat', 'Ulang materi empat pilar kebangsaan', 'Konsistensi lebih penting daripada soal sulit'],
                        'practice' => 'Kerjakan satu set TKD di Simulasi BUMN dan catat subtes terlemah.'],
                    ['title' => 'AKHLAK: Core Values BUMN', 'url' => 'https://rekrutmenbersama.fhcibumn.id', 'source' => 'Taspen (BUMN)', 'video' => ['jCgjjnrdXoo', 'AKHLAK | Core Values BUMN'],
                        'overview' => 'AKHLAK adalah nilai inti BUMN: Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif. Tes AKHLAK berupa skenario situasi kerja, pilih respons yang paling sesuai nilai.',
                        'points' => ['Amanah: memegang teguh kepercayaan', 'Kompeten: terus belajar dan mengembangkan kapabilitas', 'Harmonis: saling peduli dan menghargai perbedaan', 'Loyal: berdedikasi dan mengutamakan kepentingan bangsa', 'Adaptif: terus berinovasi dan antusias menghadapi perubahan', 'Kolaboratif: membangun kerja sama yang sinergis'],
                        'terms' => [['AKHLAK', 'Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif']],
                        'practice' => 'Tulis satu contoh pengalamanmu untuk tiap nilai AKHLAK dalam format situasi, tindakan, hasil.'],
                    ['title' => 'Bahasa Inggris dan Learning Agility', 'url' => 'https://rekrutmenbersama.fhcibumn.id', 'source' => 'FHCI BUMN',
                        'overview' => 'Tahap 2 menguji Bahasa Inggris (grammar, reading, vocabulary) dan Learning Agility, yaitu kemampuan belajar cepat dari pengalaman baru dan beradaptasi.',
                        'points' => ['Reading: latih membaca teks pendek dan cari ide pokok cepat', 'Grammar: fokus tenses, passive voice, conditional', 'Learning agility: tunjukkan contoh belajar hal baru dan memperbaiki diri'],
                        'practice' => 'Baca satu artikel bisnis bahasa Inggris per hari dan tulis ringkasan tiga kalimat.'],
                ],
            ],
            'jalur-tni-polri' => [
                'title' => 'Jalur belajar: TNI/POLRI', 'category' => 'TNI/POLRI',
                'summary' => 'Lima topik untuk seleksi TNI dan Polri: alur, psikotes kecerdasan, kecermatan, kesamaptaan jasmani, dan akademik.',
                'description' => "Jalur ini menyiapkan kamu untuk seleksi TNI dan Polri yang bertahap: administrasi, kesehatan, jasmani, psikologi, dan akademik. Tahapan dan syarat bisa berubah tiap tahun, jadi selalu cocokkan dengan pengumuman resmi.\n\nBelajar per topik lalu uji di Simulasi TNI/POLRI.",
                'topics' => [
                    ['title' => 'Alur seleksi dan persyaratan', 'url' => 'https://penerimaan.polri.go.id', 'source' => 'Polri (penerimaan.polri.go.id)',
                        'overview' => 'Pahami tahapan seleksi, persyaratan umum, dan dokumen. Polri mendaftar lewat penerimaan.polri.go.id, TNI lewat kanal resmi tni.mil.id dan pengumuman tiap matra.',
                        'points' => ['Tahapan umum: administrasi, kesehatan, jasmani, psikologi, akademik, pantukhir', 'Syarat tinggi, usia, dan pendidikan berbeda tiap jalur', 'Waspada calo: seleksi resmi tidak dipungut biaya'],
                        'practice' => 'Cek persyaratan jalur yang kamu incar dan buat daftar dokumen yang belum lengkap.'],
                    ['title' => 'Psikotes: tes kecerdasan', 'url' => 'https://penerimaan.polri.go.id', 'source' => 'Polri (penerimaan.polri.go.id)',
                        'overview' => 'Tes kecerdasan mengukur kemampuan verbal, numerik, dan figural dengan batas waktu ketat. Kecepatan dan ketelitian sama pentingnya dengan ketepatan.',
                        'points' => ['Latih deret angka, analogi, dan pola gambar', 'Pelajari pola soal sampai refleks, bukan menghitung dari nol', 'Lewati soal yang menahan lebih dari satu menit'],
                        'practice' => 'Kerjakan 20 soal deret dan analogi dengan timer, ulangi sampai waktu turun.'],
                    ['title' => 'Tes kecermatan', 'url' => 'https://penerimaan.polri.go.id', 'source' => 'Polri (penerimaan.polri.go.id)',
                        'overview' => 'Tes kecermatan menguji ketelitian, konsentrasi, dan kecepatan kerja lewat pencocokan angka, huruf, atau simbol dalam waktu singkat.',
                        'points' => ['Pertahankan ritme stabil sepanjang tes', 'Jaga fokus: satu kesalahan membuat urutan berantakan', 'Latihan rutin 10 menit per hari lebih efektif daripada maraton'],
                        'practice' => 'Latih tes kecermatan 10 menit setiap hari selama seminggu dan catat jumlah benar.'],
                    ['title' => 'Kesamaptaan jasmani', 'url' => 'https://penerimaan.polri.go.id', 'source' => 'Polri (penerimaan.polri.go.id)',
                        'overview' => 'Tes jasmani umumnya mencakup lari, pull up atau chinning, sit up, push up, dan shuttle run. Ukuran nilai berbeda untuk pria dan wanita serta tiap jalur, cek pengumuman resmi.',
                        'points' => ['Bangun daya tahan lari 12 menit bertahap', 'Latih kekuatan: push up, sit up, pull up', 'Istirahat cukup dan jaga berat badan ideal'],
                        'practice' => 'Ukur kondisi awal (lari 12 menit, push up, sit up 1 menit) lalu susun jadwal latihan 4 minggu.'],
                    ['title' => 'Akademik dan wawasan kebangsaan', 'url' => 'https://tni.mil.id', 'source' => 'TNI (tni.mil.id)',
                        'overview' => 'Tes akademik memuat pengetahuan umum, wawasan kebangsaan, dan materi dasar sesuai jalur. Pelajari Pancasila, UUD 1945, sejarah, dan kemampuan numerik dasar.',
                        'points' => ['Ulang materi kebangsaan dari jalur CPNS (TWK)', 'Perkuat matematika dasar dan bahasa Indonesia', 'Ikuti pengumuman tiap jalur untuk kisi-kisi resmi'],
                        'practice' => 'Kerjakan satu set soal akademik di Simulasi TNI/POLRI dan perbaiki topik yang salah.'],
                ],
            ],
        ];
    }
}
