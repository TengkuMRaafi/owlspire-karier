<?php

namespace App\Services\Careers;

/**
 * Menilai kualitas CV dari teksnya, tanpa AI: bagian yang ada, kontak, pengalaman (tahun), pendidikan,
 * pencapaian terukur, kata kerja aksi, dan keterbacaan. Hasilnya skor 0-100 beserta daftar masalah dan saran.
 * Semua aturan di sini heuristik, jadi hasilnya bersifat panduan, bukan vonis.
 */
class CvInsights
{
    private const SECTIONS = [
        'ringkasan' => ['label' => 'Ringkasan profil', 'h' => 'ringkasan(?: profil| diri)?|profil(?: singkat| profesional)?|tentang saya|summary|professional summary|profile|about me|objective|tujuan karier'],
        'pendidikan' => ['label' => 'Pendidikan', 'h' => 'pendidikan|riwayat pendidikan|latar belakang pendidikan|education|academic background'],
        'pengalaman' => ['label' => 'Pengalaman kerja', 'h' => 'pengalaman(?: kerja| profesional)?|riwayat pekerjaan|work experience|professional experience|employment(?: history)?|experience|magang|internship'],
        'skill' => ['label' => 'Keahlian', 'h' => 'skills?|technical skills?|keahlian|kemampuan|keterampilan|kompetensi|core competenc(?:y|ies)'],
        'proyek' => ['label' => 'Proyek', 'h' => 'proyek|projects?|portofolio|portfolio'],
        'sertifikat' => ['label' => 'Sertifikat dan pelatihan', 'h' => 'sertifikat|sertifikasi|certifications?|licenses?|pelatihan|training|kursus|courses?'],
        'organisasi' => ['label' => 'Organisasi dan kegiatan', 'h' => 'organisasi|pengalaman organisasi|organi[sz]ations?|kepanitiaan|volunteer(?:ing)?|relawan|kegiatan'],
        'prestasi' => ['label' => 'Prestasi', 'h' => 'prestasi|penghargaan|awards?|achievements?|honors?'],
        'bahasa' => ['label' => 'Bahasa', 'h' => 'bahasa|languages?'],
    ];

    private const ACTION_VERBS = 'mengembangkan|membangun|merancang|memimpin|mengelola|meningkatkan|menganalisis|membuat|mengimplementasikan|mengoptimalkan|menyusun|mengoordinasikan|mengoordinir|menyelesaikan|memelihara|mengajar|membimbing|merencanakan|menjalankan|mengotomatisasi|menurunkan|mengurangi|menghasilkan|meluncurkan|menerapkan|memastikan|memperbaiki|memonitor|menangani|melatih|menjual|menegosiasikan|mempresentasikan|'
        .'developed|built|designed|led|managed|improved|analy[sz]ed|created|implemented|optimi[sz]ed|delivered|coordinated|launched|deployed|automated|reduced|increased|achieved|organi[sz]ed|trained|mentored|negotiated|presented|maintained|resolved|streamlined|established';

    private const PERSONAL_DATA = 'agama|status pernikahan|status perkawinan|tinggi badan|berat badan|golongan darah|jenis kelamin|tempat(?:,)? tanggal lahir|tempat\/tgl lahir|kewarganegaraan|marital status|religion|height|weight|date of birth';

    private const EDU_WORDS = '\b(?:s1|s2|s3|d3|d4|d-?iii|sma|smk|man|universitas|politeknik|institut|sekolah tinggi|akademi|ipk|gpa|jurusan|fakultas|bachelor|master|university|college|school)\b';

    private const QUANT = '/(?:\d+[.,]?\d*\s?(?:%|persen)|\brp\.?\s?\d|\d+\s?(?:juta|ribu|jt|rb|miliar)\b|\d+\s?\+?\s?(?:orang|pengguna|users?|klien|clients?|pelanggan|customers?|anggota|members?|proyek|projects?|tim\b|team|peserta|siswa|transaksi|aplikasi|modul)|(?:meningkat|menurun|menurunkan|mengurangi|menghemat|increased|reduced|improved|saved|growth)[^.\n]{0,50}\d)/iu';

    private const PASSIVE = '/bertanggung jawab (?:atas|untuk|terhadap)|responsible for|ditugaskan untuk/iu';

    private const MONTH = '(?:jan|feb|mar|apr|mei|may|jun|jul|agu|aug|sep|okt|oct|nov|des|dec)[a-z]*\.?';

    /** @param string[] $skillNames skill yang sudah terbaca dari kamus (untuk komponen skor skill). */
    public function analyze(string $text, array $skillNames = []): array
    {
        $text = trim(preg_replace("/[ \t]+/u", ' ', str_replace("\r", '', $text)));
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== ''));
        $low = mb_strtolower($text);
        $words = preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'+#.\/-]*/u', $text);

        $blocks = $this->sectionBlocks($lines);
        $sections = $this->sections($low, $blocks);
        $contact = $this->contact($text, $low);
        $experience = $this->experience($lines, $blocks);
        $education = $this->education($low);
        $metrics = $this->metrics($lines, $low, $words);

        $parts = $this->score($sections, $contact, $experience, $metrics, count($skillNames), $words, $lines);
        $score = (int) round(array_sum(array_column($parts, 'score')));

        return [
            'score' => max(0, min(100, $score)),
            'grade' => $this->grade($score),
            'parts' => $parts,
            'sections' => $sections,
            'contact' => $contact,
            'experience' => $experience,
            'education' => $education,
            'metrics' => $metrics,
            'issues' => $this->issues($sections, $contact, $experience, $metrics, count($skillNames), $words, $low),
            'strengths' => $this->strengths($sections, $contact, $experience, $metrics, count($skillNames), $education),
        ];
    }

    /** Pecah baris CV menjadi blok per judul bagian (kunci => baris isi). Baris sebelum judul pertama masuk 'kontak'. */
    private function sectionBlocks(array $lines): array
    {
        $blocks = ['_head' => []];
        $current = '_head';
        foreach ($lines as $line) {
            $key = $this->headingKey($line);
            if ($key !== null) {
                $current = $key;
                $blocks[$current] ??= [];

                continue;
            }
            $blocks[$current][] = $line;
        }

        return $blocks;
    }

    /** Kunci bagian bila baris ini sebuah judul (mis. "PENDIDIKAN"), selain itu null. */
    public function sectionKey(string $line): ?string
    {
        return $this->headingKey($line);
    }

    private function headingKey(string $line): ?string
    {
        if (mb_strlen($line) > 45) {
            return null;
        }
        $clean = trim(preg_replace('/^[\W_]+|[\W_]+$/u', '', $line));
        if ($clean === '') {
            return null;
        }
        foreach (self::SECTIONS as $key => $def) {
            if (preg_match('/^(?:'.$def['h'].')(?:\s*(?:&|dan|and)\s*\p{L}+)?$/iu', $clean)) {
                return $key;
            }
        }

        return null;
    }

    private function sections(string $low, array $blocks): array
    {
        $out = [];
        foreach (self::SECTIONS as $key => $def) {
            $out[$key] = ['label' => $def['label'], 'found' => isset($blocks[$key]) || $this->impliedSection($key, $low)];
        }

        return $out;
    }

    /** CV tanpa judul bagian (mis. teks yang ditempel) tetap dianggap punya bagian bila isinya jelas terlihat. */
    private function impliedSection(string $key, string $low): bool
    {
        return match ($key) {
            'pendidikan' => (bool) preg_match('/'.self::EDU_WORDS.'/iu', $low),
            'pengalaman' => (bool) preg_match('/(?:pengalaman|experience|bekerja sebagai|worked as|magang|intern)/iu', $low) && (bool) preg_match('/(?:19|20)\d{2}/', $low),
            'skill' => (bool) preg_match('/(?:keahlian|skills?|kemampuan|menguasai)\s*:/iu', $low),
            default => false,
        };
    }

    private function contact(string $text, string $low): array
    {
        return [
            'email' => (bool) preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $text),
            'phone' => (bool) preg_match('/(?:\+62|\b62|\b0)[\s\-.]?8\d[\d\s\-.]{6,12}\d/', $text) || (bool) preg_match('/\+?\d[\d\s\-().]{8,16}\d/', $text),
            'linkedin' => str_contains($low, 'linkedin.com/'),
            'portfolio' => (bool) preg_match('/github\.com|gitlab\.com|behance\.net|dribbble\.com|medium\.com|portfolio|https?:\/\//i', $low),
            'location' => (bool) preg_match('/\b(?:alamat|domisili|address|location|kota|jl\.|jalan|kab\.|kec\.|jakarta|bandung|surabaya|medan|pekanbaru|yogyakarta|semarang|makassar|palembang|batam|depok|bekasi|tangerang|bogor|malang|denpasar)\b/iu', $low),
        ];
    }

    /** Hitung lama pengalaman kerja dari rentang tanggal (digabung bila tumpang tindih; rentang pendidikan diabaikan). */
    private function experience(array $lines, array $blocks): array
    {
        $scope = isset($blocks['pengalaman']) && count($blocks['pengalaman']) > 0 ? $blocks['pengalaman'] : $lines;
        $useAll = $scope === $lines;
        $now = (int) date('Y') * 12 + (int) date('n');
        $spans = [];

        $pat = '/(?:('.self::MONTH.')\s*)?((?:19|20)\d{2})\s*(?:-|–|—|s\.?d\.?|sampai|hingga|to|until)\s*(?:('.self::MONTH.')\s*)?((?:19|20)\d{2}|sekarang|saat ini|present|now|current|kini)/iu';
        foreach ($scope as $i => $line) {
            if ($useAll && (preg_match('/'.self::EDU_WORDS.'/iu', $line) || ($i > 0 && preg_match('/'.self::EDU_WORDS.'/iu', $scope[$i - 1] ?? '') && mb_strlen($line) < 40))) {
                continue; // di CV tanpa judul bagian, lewati rentang yang berdekatan dengan kata pendidikan
            }
            if (! preg_match_all($pat, $line, $m, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($m as $r) {
                $start = (int) $r[2] * 12 + $this->monthNumber($r[1] ?? '', 1);
                $endRaw = mb_strtolower($r[4]);
                $end = ctype_digit($endRaw) ? (int) $endRaw * 12 + $this->monthNumber($r[3] ?? '', 12) : $now;
                if ($start >= 1990 * 12 && $end >= $start && $end <= $now + 12) {
                    $spans[] = [$start, min($end, $now)];
                }
            }
        }

        usort($spans, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($spans as $s) {
            if ($merged && $s[0] <= $merged[count($merged) - 1][1]) {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $s[1]);
            } else {
                $merged[] = $s;
            }
        }
        $months = array_sum(array_map(fn ($s) => $s[1] - $s[0] + 1, $merged));

        return ['ranges' => count($spans), 'years' => $months > 0 ? round($months / 12, 1) : null];
    }

    private function monthNumber(string $name, int $default): int
    {
        if ($name === '') {
            return $default;
        }
        $map = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6, 'jul' => 7, 'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12];

        return $map[mb_substr(mb_strtolower($name), 0, 3)] ?? $default;
    }

    private function education(string $low): array
    {
        $level = null;
        foreach ([
            'S3' => '/\b(?:s3|doktor|ph\.?d)\b/u', 'S2' => '/\b(?:s2|magister|master)\b/u', 'S1' => '/\b(?:s1|sarjana|bachelor|d4|d-?iv)\b/u',
            'D3' => '/\b(?:d3|d-?iii|diploma)\b/u', 'SMA/SMK' => '/\b(?:sma|smk|man|high school)\b/u',
        ] as $name => $re) {
            if (preg_match($re, $low)) {
                $level = $name;
                break;
            }
        }
        $gpa = null;
        if (preg_match('/\b(?:ipk|gpa)\s*[:=]?\s*([0-4](?:[.,]\d{1,2})?)\b/u', $low, $m)) {
            $gpa = (float) str_replace(',', '.', $m[1]);
        }

        return ['level' => $level, 'gpa' => $gpa];
    }

    private function metrics(array $lines, string $low, int $words): array
    {
        $quant = self::QUANT;
        $numbers = 0;
        foreach ($lines as $line) {
            if (mb_strlen($line) > 25 && preg_match($quant, $line)) {
                $numbers++;
            }
        }
        $verbs = preg_match_all('/\b(?:'.self::ACTION_VERBS.')\b/iu', $low);
        $bullets = 0;
        foreach ($lines as $line) {
            if (preg_match('/^[-•*▪●◦►➢✓]\s*\S/u', $line)) {
                $bullets++;
            }
        }
        $avg = $lines ? array_sum(array_map('mb_strlen', $lines)) / count($lines) : 0;

        return ['words' => $words, 'numbers' => $numbers, 'verbs' => (int) $verbs, 'bullets' => $bullets, 'avg_line' => (int) round($avg)];
    }

    private function score(array $sections, array $contact, array $exp, array $m, int $skills, int $words, array $lines): array
    {
        $f = fn (string $k) => $sections[$k]['found'] ?? false;

        // 1) Kontak dan identitas (15)
        $c = ($contact['email'] ? 5 : 0) + ($contact['phone'] ? 5 : 0) + (($contact['linkedin'] || $contact['portfolio']) ? 3 : 0) + ($contact['location'] ? 2 : 0);

        // 2) Kelengkapan bagian (25)
        $s = ($f('pendidikan') ? 7 : 0) + (($f('pengalaman') || $f('proyek')) ? 7 : 0) + ($f('skill') ? 6 : 0) + ($f('ringkasan') ? 3 : 0)
            + (($f('sertifikat') || $f('organisasi') || $f('prestasi') || $f('proyek')) ? 2 : 0);

        // 3) Keahlian terbaca (15)
        $k = min(15, $skills * 2);

        // 4) Pengalaman dan pencapaian (25)
        $e = min(10, ($exp['ranges'] >= 1 ? 5 : 0) + ($exp['ranges'] >= 2 ? 2 : 0) + (($exp['years'] ?? 0) >= 1 ? 2 : 0) + (($exp['years'] ?? 0) >= 3 ? 1 : 0))
            + min(8, $m['numbers'] >= 1 ? 4 + min(4, ($m['numbers'] - 1) * 2) : 0)
            + ($m['verbs'] >= 6 ? 7 : ($m['verbs'] >= 3 ? 4 : ($m['verbs'] >= 1 ? 2 : 0)));

        // 5) Format dan keterbacaan (20)
        $len = $words >= 250 && $words <= 900 ? 10 : (($words >= 150 && $words < 250) || ($words > 900 && $words <= 1300) ? 5 : ($words >= 80 ? 2 : 0));
        $fmt = $len + ($m['bullets'] >= 4 ? 4 : ($m['bullets'] >= 1 ? 2 : 0)) + ($m['avg_line'] > 0 && $m['avg_line'] <= 140 ? 3 : 0) + $this->readability($lines);

        return [
            'kontak' => ['label' => 'Kontak dan identitas', 'score' => $c, 'max' => 15],
            'bagian' => ['label' => 'Kelengkapan bagian', 'score' => $s, 'max' => 25],
            'skill' => ['label' => 'Keahlian terbaca', 'score' => $k, 'max' => 15],
            'pengalaman' => ['label' => 'Pengalaman dan pencapaian', 'score' => $e, 'max' => 25],
            'format' => ['label' => 'Format dan keterbacaan', 'score' => min(20, $fmt), 'max' => 20],
        ];
    }

    /** 0-3: teks hasil ekstraksi yang rusak (banyak simbol aneh) menandakan CV sulit dibaca sistem ATS. */
    private function readability(array $lines): int
    {
        $all = implode(' ', array_slice($lines, 0, 200));
        $len = max(1, mb_strlen($all));
        $odd = preg_match_all('/[^\p{L}\p{N}\s.,:;()\/@+#%&\'"\-–—•*\[\]!?_=<>]/u', $all);
        $ratio = $odd / $len;

        return $ratio < 0.01 ? 3 : ($ratio < 0.03 ? 2 : ($ratio < 0.06 ? 1 : 0));
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Sangat baik',
            $score >= 60 => 'Baik',
            $score >= 40 => 'Cukup',
            default => 'Perlu banyak perbaikan',
        };
    }

    private function issues(array $sections, array $contact, array $exp, array $m, int $skills, int $words, string $low): array
    {
        $f = fn (string $k) => $sections[$k]['found'] ?? false;
        $i = [];
        $add = function (string $level, string $title, string $tip) use (&$i) {
            $i[] = ['level' => $level, 'title' => $title, 'tip' => $tip];
        };

        if (! $contact['email']) {
            $add('high', 'Email tidak terbaca', 'Cantumkan email profesional (nama.kamu@...) di bagian paling atas agar rekruter bisa menghubungimu.');
        }
        if (! $contact['phone']) {
            $add('high', 'Nomor telepon tidak terbaca', 'Tambahkan nomor WhatsApp/telepon aktif dengan format yang jelas, mis. 0812-3456-7890.');
        }
        if (! $contact['linkedin'] && ! $contact['portfolio']) {
            $add('low', 'Belum ada tautan profil atau portofolio', 'Tambahkan LinkedIn, GitHub, atau portofolio online. Ini sangat membantu untuk posisi teknis dan kreatif.');
        }
        if (! $f('pengalaman') && ! $f('proyek')) {
            $add('high', 'Tidak ada pengalaman atau proyek', 'Tulis pengalaman kerja, magang, atau proyek. Bila belum punya pengalaman kerja, proyek kuliah dan organisasi sudah cukup.');
        }
        if (! $f('pendidikan')) {
            $add('high', 'Bagian pendidikan tidak terbaca', 'Cantumkan jenjang, jurusan, institusi, tahun, dan IPK bila di atas 3,00.');
        }
        if (! $f('skill') || $skills < 3) {
            $add($skills < 3 ? 'high' : 'medium', 'Keahlian yang terbaca masih sedikit', 'Buat bagian "Keahlian" berisi daftar skill teknis dan nonteknis dengan penulisan standar (mis. "Microsoft Excel", "SQL", "Public Speaking").');
        }
        if (! $f('ringkasan')) {
            $add('medium', 'Belum ada ringkasan profil', 'Awali CV dengan 2 sampai 3 kalimat: siapa kamu, keahlian utama, dan posisi yang dituju.');
        }
        if ($f('pengalaman') && $m['numbers'] === 0) {
            $add('high', 'Belum ada pencapaian terukur', 'Ubah tugas menjadi hasil dengan angka, mis. "Meningkatkan penjualan 20% dalam 3 bulan" atau "Melayani 50 pelanggan per hari".');
        } elseif ($m['numbers'] > 0 && $m['numbers'] < 3) {
            $add('medium', 'Pencapaian terukur masih sedikit', 'Usahakan minimal 3 poin yang memakai angka, persentase, atau skala (jumlah pengguna, tim, nilai proyek).');
        }
        if ($m['verbs'] < 3) {
            $add('medium', 'Kata kerja aksi masih kurang', 'Awali poin dengan kata kerja kuat seperti mengembangkan, memimpin, menganalisis, mengoptimalkan, bukan sekadar "bertanggung jawab atas".');
        }
        if (preg_match('/bertanggung jawab (?:atas|untuk|terhadap)|responsible for|ditugaskan untuk/iu', $low)) {
            $add('low', 'Ada frasa pasif "bertanggung jawab atas"', 'Ganti dengan hasil yang kamu capai. Rekruter ingin tahu dampakmu, bukan sekadar daftar tugas.');
        }
        if ($m['bullets'] < 3) {
            $add('low', 'Hampir tidak ada poin berbutir', 'Pecah paragraf panjang menjadi poin-poin singkat (satu baris, satu pencapaian) agar mudah dipindai dalam 6 detik pertama.');
        }
        if ($words > 1300) {
            $add('medium', 'CV terlalu panjang', 'Ringkas menjadi 1 sampai 2 halaman. Buang pengalaman lama yang tidak relevan dengan posisi tujuan.');
        } elseif ($words < 150) {
            $add('high', 'CV sangat singkat', 'Teks yang terbaca hanya sedikit. Lengkapi pengalaman, proyek, dan keahlian, atau periksa apakah berkasnya hasil scan/gambar.');
        }
        if (preg_match('/\b(?:'.self::PERSONAL_DATA.')\b/iu', $low)) {
            $add('low', 'Ada data pribadi yang tidak perlu', 'Agama, status pernikahan, tinggi/berat badan, dan golongan darah sebaiknya dihapus kecuali lowongan memintanya.');
        }
        if ($f('pengalaman') && $exp['ranges'] === 0) {
            $add('medium', 'Rentang tanggal pengalaman tidak terbaca', 'Tulis periode tiap pengalaman dengan format jelas, mis. "Jan 2023 - Des 2024" atau "2022 - sekarang".');
        }

        usort($i, fn ($a, $b) => ['high' => 0, 'medium' => 1, 'low' => 2][$a['level']] <=> ['high' => 0, 'medium' => 1, 'low' => 2][$b['level']]);

        return $i;
    }

    private function strengths(array $sections, array $contact, array $exp, array $m, int $skills, array $edu): array
    {
        $s = [];
        if ($contact['email'] && $contact['phone']) {
            $s[] = 'Kontak lengkap dan mudah ditemukan.';
        }
        if ($skills >= 6) {
            $s[] = "{$skills} skill terbaca dari kamus, cukup kaya untuk pencocokan karier.";
        }
        if (($exp['years'] ?? 0) >= 1) {
            $s[] = 'Pengalaman kerja terbaca sekitar '.rtrim(rtrim(number_format($exp['years'], 1, ',', ''), '0'), ',').' tahun.';
        }
        if ($m['numbers'] >= 3) {
            $s[] = 'Pencapaian dituliskan dengan angka, ini nilai plus besar bagi rekruter.';
        }
        if ($m['verbs'] >= 6) {
            $s[] = 'Pemakaian kata kerja aksi sudah baik.';
        }
        if (($edu['gpa'] ?? 0) >= 3.5) {
            $s[] = 'IPK tercantum dan kuat.';
        }
        if (($sections['proyek']['found'] ?? false) || ($sections['sertifikat']['found'] ?? false)) {
            $s[] = 'Ada proyek atau sertifikat yang memperkuat keahlian.';
        }

        return $s;
    }

    /* ------------------------------------------------------------------
     * Pratinjau CV bertanda panah (Analisis CV)
     * ------------------------------------------------------------------ */

    /** Judul bagian yang boleh disarankan AI, dan label tampilannya. */
    public static function sectionLabels(): array
    {
        $out = ['kontak' => 'Identitas dan kontak', 'isi' => 'Isi CV'];
        foreach (self::SECTIONS as $key => $def) {
            $out[$key] = $def['label'];
        }

        return $out;
    }

    /**
     * Pecah teks CV menjadi blok per judul bagian, urutannya mengikuti CV asli.
     *
     * @return array<int,array{key:string,label:string,lines:array<int,array{t:string,b:bool,m:?string}>,more:int,ghost:bool,notes:array}>
     */
    public function blocks(string $text, int $maxLines = 14): array
    {
        $text = trim(preg_replace("/[ \t]+/u", ' ', str_replace("\r", '', $text)));
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== ''));

        $map = ['kontak' => []];
        $cur = 'kontak';
        foreach ($lines as $line) {
            $key = $this->headingKey($line);
            if ($key !== null) {
                $cur = $key;
                $map[$cur] ??= [];

                continue;
            }
            $map[$cur][] = $line;
        }

        $labels = self::sectionLabels();
        $onlyHead = count(array_filter($map, fn ($v, $k) => $k !== 'kontak', ARRAY_FILTER_USE_BOTH)) === 0;
        if ($onlyHead) {
            $map = ['isi' => $map['kontak']];
        } elseif ($map['kontak'] === []) {
            unset($map['kontak']);
        }

        $out = [];
        foreach ($map as $key => $raw) {
            $limit = $key === 'kontak' ? 8 : ($key === 'isi' ? 40 : $maxLines);
            $items = [];
            foreach (array_slice($raw, 0, $limit) as $l) {
                $bullet = (bool) preg_match('/^[-•*▪●◦►➢✓]\s*\S/u', $l);
                $t = $bullet ? trim(preg_replace('/^[-•*▪●◦►➢✓]\s*/u', '', $l)) : $l;
                $items[] = ['t' => mb_substr($t, 0, 360), 'b' => $bullet, 'm' => null];
            }
            $out[] = ['key' => $key, 'label' => $labels[$key] ?? ucfirst($key), 'lines' => $items, 'more' => max(0, count($raw) - $limit), 'ghost' => false, 'notes' => []];
        }

        return $out;
    }

    /**
     * Pratinjau hasil Analisis CV: tiap blok diberi catatan (bagus / kurang menonjol / perlu diperbaiki) dan
     * baris tertentu ditandai. Bagian penting yang belum ada ditambahkan sebagai blok kosong.
     *
     * @param  string[]  $skillNames  skill terbaca dari kamus
     * @param  string[]  $careerMissing  skill yang diminta karier teratas tapi belum ada di CV
     * @param  array|null  $ats  hasil CvAtsKeywords::analyze bila CV dianalisis dalam mode ATS (menambah catatan kata kunci dan format)
     * @return array{blocks:array,counts:array{good:int,weak:int,bad:int}}
     */
    public function preview(string $text, array $insights, array $skillNames = [], ?string $career = null, array $careerMissing = [], ?array $ats = null): array
    {
        $blocks = $this->blocks($text);
        $sec = $insights['sections'] ?? [];
        $found = fn (string $k) => (bool) ($sec[$k]['found'] ?? false);
        $contact = $insights['contact'] ?? [];
        $m = $insights['metrics'] ?? ['numbers' => 0, 'verbs' => 0];
        $exp = $insights['experience'] ?? ['ranges' => 0, 'years' => null];
        $edu = $insights['education'] ?? ['level' => null, 'gpa' => null];
        $nSkills = count($skillNames);
        $note = fn (string $t, string $h, string $p) => ['t' => $t, 'h' => $h, 'p' => $p, 'r' => null];

        // Mode ATS: kata kunci jabatan tujuan yang belum muncul, dan pemeriksaan format yang gagal.
        $atsRole = $ats['role'] ?? null;
        $atsMissing = $ats ? collect($ats['missing'] ?? [])->where('group', 'hard')->pluck('label')->take(6)->all() : [];
        $atsCoverage = (int) ($ats['coverage'] ?? 0);
        $atsFailed = $ats ? collect($ats['checks'] ?? [])->where('ok', false)->keyBy('key') : collect();

        foreach ($blocks as &$b) {
            // Tandai baris: hijau bila pencapaiannya terukur, kuning bila berupa kalimat pasif atau data pribadi yang tidak perlu.
            foreach ($b['lines'] as &$ln) {
                if (preg_match(self::PASSIVE, $ln['t']) || preg_match('/\b(?:'.self::PERSONAL_DATA.')\b/iu', $ln['t'])) {
                    $ln['m'] = 'weak';
                } elseif (mb_strlen($ln['t']) > 25 && preg_match(self::QUANT, $ln['t']) && in_array($b['key'], ['pengalaman', 'proyek', 'organisasi', 'prestasi', 'isi'], true)) {
                    $ln['m'] = 'good';
                }
            }
            unset($ln);

            $notes = [];
            switch ($b['key']) {
                case 'kontak':
                case 'isi':
                    if ($b['key'] === 'isi') {
                        $notes[] = $note('bad', 'Judul bagian tidak terbaca', 'CV belum punya judul bagian yang tegas. Beri judul seperti Pendidikan, Pengalaman, dan Keahlian supaya mudah dibaca rekruter dan sistem ATS.');
                    }
                    $miss = array_filter(['email' => 'email', 'phone' => 'nomor telepon'], fn ($label, $k) => ! ($contact[$k] ?? false), ARRAY_FILTER_USE_BOTH);
                    if ($miss) {
                        $notes[] = $note('bad', 'Kontak belum lengkap', ucfirst(implode(' dan ', $miss)).' tidak terbaca. Cantumkan di bagian paling atas agar rekruter bisa menghubungimu.');
                    } else {
                        $notes[] = $note('good', 'Kontak lengkap', 'Email dan nomor telepon mudah ditemukan di bagian atas.');
                    }
                    if (! ($contact['linkedin'] ?? false) && ! ($contact['portfolio'] ?? false)) {
                        $notes[] = $note('weak', 'Tautan profil belum menonjol', 'Tambahkan LinkedIn, GitHub, atau portofolio online di dekat kontak. Ini sangat membantu untuk posisi teknis dan kreatif.');
                    }
                    if ($atsFailed->has('plain')) {
                        $notes[] = $note('bad', 'Kurang ramah ATS', $atsFailed['plain']['tip']);
                    }
                    if ($atsFailed->has('role')) {
                        $notes[] = $note('weak', 'Jabatan tujuan belum tampil', $atsFailed['role']['tip']);
                    }
                    break;

                case 'ringkasan':
                    $w = array_sum(array_map(fn ($l) => str_word_count($l['t']), $b['lines']));
                    if ($w < 8) {
                        $notes[] = $note('weak', 'Ringkasan terlalu singkat', 'Tulis 2 sampai 3 kalimat: siapa kamu, keahlian utama, dan posisi yang dituju'.($career ? " (mis. {$career})" : '').'.');
                    } elseif ($w > 90) {
                        $notes[] = $note('weak', 'Ringkasan terlalu panjang', 'Rekruter hanya membaca sekilas. Padatkan menjadi 2 sampai 3 kalimat.');
                    } else {
                        $notes[] = $note('good', 'Ringkasan jelas', 'Pembuka yang menjelaskan siapa kamu dalam sekali baca.');
                    }
                    if ($atsRole && $atsFailed->has('role')) {
                        $notes[] = $note('weak', 'Sebut jabatan tujuan', "Tulis {$atsRole} di kalimat pertama ringkasan supaya ATS langsung mengaitkannya dengan lowongan.");
                    }
                    break;

                case 'pendidikan':
                    if (($edu['gpa'] ?? 0) >= 3.5) {
                        $notes[] = $note('good', 'Pendidikan kuat', 'Jenjang dan IPK tercantum jelas, ini nilai plus.');
                    } elseif ($edu['gpa'] ?? null) {
                        $notes[] = $note('good', 'Pendidikan terbaca', 'Jenjang dan IPK tercantum dengan jelas.');
                    } else {
                        $notes[] = $note('weak', 'IPK belum menonjol', 'Cantumkan IPK bila di atas 3,00, lengkap dengan jurusan, institusi, dan tahun.');
                    }
                    break;

                case 'pengalaman':
                    $n = (int) $m['numbers'];
                    if ($n === 0) {
                        $notes[] = $note('bad', 'Belum ada pencapaian terukur', 'Ubah tugas menjadi hasil dengan angka, mis. "Meningkatkan penjualan 20% dalam 3 bulan" atau "Melayani 50 pelanggan per hari".');
                    } elseif ($n < 3) {
                        $notes[] = $note('weak', 'Angka pencapaian masih sedikit', "Baru {$n} poin yang memakai angka (hijau). Usahakan minimal 3 poin dengan persentase, jumlah pengguna, atau nilai proyek.");
                    } else {
                        $notes[] = $note('good', 'Pencapaian ber-angka', "{$n} poin memakai angka (hijau). Ini yang paling dicari rekruter.");
                    }
                    if (($m['verbs'] ?? 0) < 3 || preg_match(self::PASSIVE, $text)) {
                        $notes[] = $note('weak', 'Kalimat kurang bertenaga', 'Awali poin dengan kata kerja aksi (mengembangkan, memimpin, mengoptimalkan) dan hindari "bertanggung jawab atas".');
                    }
                    if (($exp['ranges'] ?? 0) === 0) {
                        $notes[] = $note('weak', 'Periode kerja tidak terbaca', 'Tulis periode tiap pengalaman dengan format jelas, mis. "Jan 2023 - Des 2024".');
                    }
                    if ($atsRole && $atsCoverage < 60) {
                        $notes[] = $note('weak', 'Pakai kata kunci jabatan', "Sisipkan istilah yang dipakai lowongan {$atsRole} langsung di poin pengalaman (bukan hanya di daftar skill), selama memang kamu kerjakan.");
                    }
                    break;

                case 'skill':
                    if ($nSkills < 3) {
                        $notes[] = $note('bad', 'Keahlian masih sedikit', 'Tulis skill teknis dan nonteknis dengan penulisan standar, mis. "Microsoft Excel", "SQL", "Public Speaking".');
                    } elseif ($nSkills < 6) {
                        $notes[] = $note('weak', 'Keahlian belum menonjol', "{$nSkills} skill terbaca. Tambahkan yang memang kamu kuasai supaya pencocokan karier lebih tajam.");
                    } else {
                        $notes[] = $note('good', 'Keahlian kaya', "{$nSkills} skill terbaca dan cukup untuk dicocokkan dengan banyak karier.");
                    }
                    if ($atsRole) {
                        if ($atsMissing && $atsCoverage < 80) {
                            $notes[] = $note($atsCoverage < 40 ? 'bad' : 'weak', "Kata kunci {$atsRole}", 'Kata kunci yang sering dicari tapi belum tampil: '.implode(', ', $atsMissing).'. Tambahkan hanya bila memang kamu kuasai, dengan penulisan yang sama seperti di lowongan.');
                        } else {
                            $notes[] = $note('good', "Kata kunci {$atsRole}", "Kata kunci utama jabatan ini sudah banyak tampil ({$atsCoverage}%).");
                        }
                    } elseif ($career && $careerMissing) {
                        $notes[] = $note('weak', "Untuk {$career}", 'Skill yang sering diminta tapi belum tampil: '.implode(', ', array_slice($careerMissing, 0, 4)).'. Tambahkan hanya bila memang kamu kuasai.');
                    }
                    break;

                case 'proyek':
                    $notes[] = $note('good', 'Proyek memperkuat CV', 'Bukti nyata keahlian. Pastikan tiap proyek menyebut peranmu dan hasilnya.');
                    break;

                case 'sertifikat':
                case 'prestasi':
                case 'organisasi':
                    $notes[] = $note('good', 'Nilai tambah', 'Bagian ini memperkuat profilmu. Letakkan yang paling relevan di urutan teratas.');
                    break;
            }
            $b['notes'] = $notes;
        }
        unset($b);

        // Bagian penting yang belum ada ditampilkan sebagai blok kosong bertanda.
        $labels = self::sectionLabels();
        $ghost = function (string $key, string $t, string $p) use (&$blocks, $labels) {
            $blocks[] = ['key' => $key, 'label' => $labels[$key], 'lines' => [], 'more' => 0, 'ghost' => true, 'notes' => [['t' => $t, 'h' => 'Belum ada bagian ini', 'p' => $p, 'r' => null]]];
        };
        $onlyBlob = count($blocks) === 1 && $blocks[0]['key'] === 'isi';
        if (! $onlyBlob) {
            if (! $found('ringkasan')) {
                $at = ($blocks[0]['key'] ?? '') === 'kontak' ? 1 : 0;
                array_splice($blocks, $at, 0, [['key' => 'ringkasan', 'label' => $labels['ringkasan'], 'lines' => [], 'more' => 0, 'ghost' => true,
                    'notes' => [['t' => 'weak', 'h' => 'Belum ada ringkasan profil', 'p' => 'Awali CV dengan 2 sampai 3 kalimat: siapa kamu, keahlian utama, dan posisi yang dituju.'.($career ? " Arahkan ke {$career}." : ''), 'r' => null]]]]);
            }
            if (! $found('pengalaman') && ! $found('proyek')) {
                $ghost('pengalaman', 'bad', 'Tulis pengalaman kerja, magang, atau proyek. Bila belum punya pengalaman kerja, proyek kuliah dan organisasi sudah cukup.');
            }
            if (! $found('pendidikan')) {
                $ghost('pendidikan', 'bad', 'Cantumkan jenjang, jurusan, institusi, tahun, dan IPK bila di atas 3,00.');
            }
            if (! $found('skill')) {
                $ghost('skill', 'bad', 'Buat bagian Keahlian berisi daftar skill teknis dan nonteknis dengan penulisan standar.');
            }
        }

        return ['blocks' => $blocks, 'counts' => $this->tally($blocks)];
    }

    private function tally(array $blocks): array
    {
        $c = ['good' => 0, 'weak' => 0, 'bad' => 0];
        foreach ($blocks as $b) {
            foreach ($b['notes'] as $n) {
                $c[$n['t']]++;
            }
        }

        return $c;
    }
}
