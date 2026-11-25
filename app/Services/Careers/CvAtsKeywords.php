<?php

namespace App\Services\Careers;

use App\Models\Career;

/**
 * Mode CV ATS: menilai seberapa siap CV dibaca sistem ATS untuk satu jabatan tujuan.
 *
 * Kata kuncinya disusun per rumpun jabatan dari pola yang umum muncul di lowongan (alat, keahlian teknis, kata pendukung),
 * lalu diperkaya dengan skill karier di platform bila nama jabatan cocok. Ini panduan, bukan jaminan lolos:
 * tiap perusahaan punya filter sendiri, jadi selalu cocokkan lagi dengan teks lowongan yang dituju.
 */
class CvAtsKeywords
{
    /**
     * Rumpun jabatan. 'match' = pola nama jabatan, 'hard' = keahlian/alat (bobot 3), 'soft' = kata pendukung (bobot 1).
     * Isi keyword: "Label" saja, atau "Label|sinonim1|sinonim2" supaya variasi penulisan ikut terbaca.
     */
    private const FAMILIES = [
        'software' => [
            'label' => 'Software / Web Developer',
            'match' => 'developer|programmer|software|web|backend|back-end|frontend|front-end|full ?stack|engineer|pemrogram',
            'hard' => ['JavaScript|js', 'PHP', 'Laravel', 'Python', 'Java', 'React|reactjs', 'Node.js|nodejs|node js', 'HTML', 'CSS', 'SQL', 'MySQL', 'PostgreSQL', 'REST API|restful|api', 'Git', 'GitHub|gitlab', 'Docker', 'TypeScript', 'Tailwind|bootstrap', 'Unit Testing|phpunit|jest|pytest', 'MVC', 'Agile|scrum', 'Linux', 'Database|basis data'],
            'soft' => ['Problem Solving|pemecahan masalah', 'Teamwork|kerja sama tim|kerjasama tim', 'Code Review', 'Dokumentasi|documentation', 'Debugging', 'Komunikasi|communication'],
        ],
        'mobile' => [
            'label' => 'Mobile Developer',
            'match' => 'mobile|android|\bios\b|flutter|react native|kotlin|swift',
            'hard' => ['Kotlin', 'Java', 'Flutter', 'Dart', 'React Native', 'Swift', 'Android Studio', 'Firebase', 'REST API|api', 'JSON', 'Git', 'SQLite', 'MVVM', 'Play Store', 'UI/UX|ui ux', 'Jetpack Compose'],
            'soft' => ['Problem Solving|pemecahan masalah', 'Teamwork|kerja sama tim', 'Debugging', 'Agile|scrum', 'Komunikasi|communication'],
        ],
        'data-analyst' => [
            'label' => 'Data Analyst / BI',
            'match' => 'data analyst|analis data|business intelligence|\bbi\b|data analis|reporting|business analyst|analis bisnis',
            'hard' => ['SQL', 'Microsoft Excel|excel', 'Power BI|powerbi', 'Tableau', 'Looker|data studio|looker studio', 'Python', 'Pandas', 'Data Visualization|visualisasi data|dashboard', 'Statistik|statistics', 'ETL', 'Data Cleaning|data cleansing|pembersihan data', 'Google Sheets', 'A/B Testing', 'KPI', 'Data Warehouse', 'Pivot Table|vlookup|xlookup'],
            'soft' => ['Analytical Thinking|berpikir analitis|analisis', 'Storytelling', 'Presentasi|presentation', 'Komunikasi|communication', 'Problem Solving|pemecahan masalah', 'Detail|teliti|attention to detail'],
        ],
        'data-science' => [
            'label' => 'Data Scientist / AI Engineer',
            'match' => 'data scien|machine learning|\bml\b|\bai\b|artificial intelligence|deep learning|nlp|ilmuwan data',
            'hard' => ['Python', 'Machine Learning|pembelajaran mesin', 'Deep Learning', 'TensorFlow', 'PyTorch', 'Scikit-learn|sklearn', 'Pandas', 'NumPy', 'NLP|natural language processing', 'Computer Vision', 'SQL', 'Statistik|statistics', 'Model Deployment|mlops|deployment', 'Jupyter', 'Feature Engineering', 'Data Preprocessing|preprocessing', 'Hugging Face|transformers|bert', 'Git', 'Docker', 'FastAPI|flask'],
            'soft' => ['Problem Solving|pemecahan masalah', 'Riset|research', 'Presentasi|presentation', 'Komunikasi|communication', 'Analytical Thinking|berpikir analitis'],
        ],
        'ui-ux' => [
            'label' => 'UI/UX Designer',
            'match' => '\bui\b|\bux\b|designer|desainer|desain produk|product design|graphic|grafis|visual',
            'hard' => ['Figma', 'Wireframe', 'Prototype|prototyping|prototipe', 'User Research|riset pengguna', 'Usability Testing|uji kegunaan', 'Design System', 'User Flow', 'Adobe XD', 'Adobe Photoshop|photoshop', 'Adobe Illustrator|illustrator', 'Responsive Design', 'Persona', 'Information Architecture', 'Accessibility|aksesibilitas', 'Design Thinking'],
            'soft' => ['Kreativitas|creativity|kreatif', 'Kolaborasi|collaboration', 'Komunikasi|communication', 'Presentasi|presentation', 'Empati|empathy'],
        ],
        'marketing' => [
            'label' => 'Digital Marketing / Konten',
            'match' => 'marketing|pemasaran|social media|media sosial|content|konten|seo|copywrit|brand|digital ads|community',
            'hard' => ['SEO', 'Google Analytics|ga4', 'Google Ads', 'Meta Ads|facebook ads|instagram ads', 'Content Marketing|konten', 'Social Media|media sosial', 'Copywriting', 'Email Marketing', 'Canva', 'Campaign|kampanye', 'Conversion|konversi', 'ROI', 'Engagement', 'Content Calendar|kalender konten', 'TikTok', 'Brand Awareness', 'Market Research|riset pasar', 'A/B Testing', 'CRM'],
            'soft' => ['Kreativitas|creativity|kreatif', 'Komunikasi|communication', 'Analisis|analytical', 'Kolaborasi|collaboration', 'Manajemen waktu|time management'],
        ],
        'hr' => [
            'label' => 'HR / Rekrutmen',
            'match' => '\bhr\b|human resource|sdm|rekrut|recruit|talent|people|personalia|hrd|payroll',
            'hard' => ['Recruitment|rekrutmen', 'Talent Acquisition', 'Screening CV|screening|seleksi', 'Interview|wawancara', 'Onboarding', 'Payroll|penggajian', 'Employee Relations|hubungan industrial', 'Performance Appraisal|penilaian kinerja|performance review', 'Training|pelatihan', 'HRIS', 'Undang-Undang Ketenagakerjaan|ketenagakerjaan|labor law', 'BPJS', 'Employer Branding', 'Job Posting|job description|deskripsi pekerjaan', 'Administrasi Karyawan|administrasi'],
            'soft' => ['Komunikasi|communication', 'Negosiasi|negotiation', 'Empati|empathy', 'Kerahasiaan|confidential', 'Organisasi|organizing', 'Interpersonal'],
        ],
        'finance' => [
            'label' => 'Akuntansi / Keuangan',
            'match' => 'akunt|accounting|finance|keuangan|pajak|tax|auditor|audit|bookkeep|kasir|treasury',
            'hard' => ['Akuntansi|accounting', 'Laporan Keuangan|financial statement|financial report', 'Jurnal|journal entry', 'Rekonsiliasi|reconciliation', 'Accurate|zahir|myob|sap|oracle', 'Microsoft Excel|excel', 'Perpajakan|pajak|tax', 'PPh|ppn', 'Accounts Payable|hutang usaha', 'Accounts Receivable|piutang', 'Budgeting|anggaran', 'Audit', 'Buku Besar|general ledger', 'Cash Flow|arus kas', 'PSAK|ifrs'],
            'soft' => ['Ketelitian|teliti|detail', 'Integritas|integrity', 'Analitis|analytical', 'Manajemen waktu|time management', 'Komunikasi|communication'],
        ],
        'admin' => [
            'label' => 'Administrasi / Perkantoran',
            'match' => 'admin|administrasi|sekretaris|secretary|staff|staf|office|resepsionis|receptionist|clerk|operator|back office',
            'hard' => ['Microsoft Office|ms office', 'Microsoft Excel|excel', 'Microsoft Word|ms word', 'PowerPoint', 'Google Workspace|google docs|google sheets', 'Pengarsipan|filing|arsip', 'Data Entry|input data', 'Korespondensi|correspondence|surat menyurat', 'Pembukuan|bookkeeping', 'Inventaris|inventory', 'Jadwal|scheduling|penjadwalan', 'Laporan|reporting', 'Notulen|minutes of meeting|mom', 'Purchase Order|po'],
            'soft' => ['Ketelitian|teliti|detail', 'Organisasi|organizing|terorganisir', 'Komunikasi|communication', 'Manajemen waktu|time management', 'Multitasking', 'Inisiatif|initiative'],
        ],
        'cs' => [
            'label' => 'Customer Service',
            'match' => 'customer service|customer support|\bcs\b|pelayanan|layanan pelanggan|call center|contact center|helpdesk|help desk|customer care',
            'hard' => ['Customer Service|layanan pelanggan|pelayanan pelanggan', 'Komplain|complaint|keluhan', 'CRM', 'Live Chat', 'Zendesk|freshdesk', 'Ticketing|tiket', 'SLA', 'Call Center|contact center', 'Product Knowledge|pengetahuan produk', 'Customer Satisfaction|kepuasan pelanggan|csat', 'Follow Up|tindak lanjut', 'WhatsApp Business', 'Email Support', 'Upselling|cross-selling'],
            'soft' => ['Komunikasi|communication', 'Empati|empathy', 'Kesabaran|patience|sabar', 'Problem Solving|pemecahan masalah', 'Ramah|friendly', 'Multitasking'],
        ],
        'sales' => [
            'label' => 'Sales / Business Development',
            'match' => 'sales|penjualan|business development|bisnis|account executive|account manager|marketing executive|sales executive|\bbd\b',
            'hard' => ['Target Penjualan|sales target', 'Prospecting|prospek', 'Negosiasi|negotiation', 'Closing', 'CRM', 'Lead Generation', 'Presentasi|presentation|presentasi produk', 'Key Account|account management', 'Pipeline', 'B2B', 'B2C', 'Cold Calling|cold call', 'Proposal|penawaran', 'Market Research|riset pasar', 'After Sales', 'Revenue|omzet|pendapatan'],
            'soft' => ['Komunikasi|communication', 'Persuasi|persuasion', 'Networking|jaringan', 'Resilience|ketahanan', 'Inisiatif|initiative', 'Kerja sama tim|teamwork'],
        ],
        'pm' => [
            'label' => 'Project / Product Manager',
            'match' => 'project manager|product manager|\bpm\b|project|product owner|scrum master|program manager|manajer proyek',
            'hard' => ['Project Management|manajemen proyek', 'Agile', 'Scrum', 'Kanban', 'Jira', 'Roadmap', 'Stakeholder', 'Risk Management|manajemen risiko', 'Budgeting|anggaran', 'Timeline|jadwal proyek', 'Product Requirement|prd|user story', 'KPI|okr', 'Sprint', 'Trello|asana|notion', 'Gantt', 'PMP|prince2'],
            'soft' => ['Kepemimpinan|leadership', 'Komunikasi|communication', 'Negosiasi|negotiation', 'Problem Solving|pemecahan masalah', 'Koordinasi|coordination', 'Pengambilan keputusan|decision making'],
        ],
        'it-support' => [
            'label' => 'IT Support / Jaringan / Infrastruktur',
            'match' => 'it support|helpdesk|network|jaringan|system admin|sysadmin|infrastruk|devops|cloud|technician|teknisi|\bnoc\b',
            'hard' => ['Troubleshooting', 'Windows Server|windows', 'Linux', 'Networking|jaringan komputer|jaringan', 'TCP/IP', 'Mikrotik', 'Cisco|ccna', 'VLAN', 'DNS', 'Active Directory', 'Virtualization|vmware|proxmox', 'AWS|azure|gcp|cloud', 'Docker', 'CI/CD', 'Backup', 'Monitoring|zabbix|grafana', 'Hardware', 'Ticketing|helpdesk'],
            'soft' => ['Problem Solving|pemecahan masalah', 'Komunikasi|communication', 'Kerja sama tim|teamwork', 'Dokumentasi|documentation', 'Kesabaran|patience'],
        ],
        'security' => [
            'label' => 'Cybersecurity / SOC',
            'match' => 'security|keamanan|cyber|\bsoc\b|pentest|penetration|infosec|siber',
            'hard' => ['Cybersecurity|keamanan siber|keamanan informasi', 'SIEM', 'Splunk|wazuh|qradar', 'Incident Response|penanganan insiden', 'Penetration Testing|pentest', 'Vulnerability Assessment|vulnerability', 'Network Security', 'Firewall', 'OWASP', 'Threat Intelligence|threat hunting', 'ISO 27001', 'Linux', 'Wireshark', 'Malware Analysis', 'Log Analysis|analisis log', 'Kali Linux|burp suite|nmap'],
            'soft' => ['Analytical Thinking|berpikir analitis', 'Detail|teliti', 'Problem Solving|pemecahan masalah', 'Komunikasi|communication', 'Dokumentasi|documentation'],
        ],
        'teacher' => [
            'label' => 'Guru / Pendidik',
            'match' => 'guru|teacher|dosen|lecturer|pengajar|tutor|instruktur|pendidik|pendidikan',
            'hard' => ['Kurikulum Merdeka|kurikulum', 'Modul Ajar|rpp', 'Penilaian|assessment|asesmen', 'Pembelajaran Berbasis Proyek|pjbl|project based learning', 'Manajemen Kelas|classroom management', 'Media Pembelajaran|media ajar', 'Google Classroom|lms|moodle', 'Evaluasi|evaluation', 'Differentiated Learning|pembelajaran berdiferensiasi', 'Bimbingan|mentoring', 'Pedagogi|pedagogy', 'Penelitian Tindakan Kelas|ptk'],
            'soft' => ['Komunikasi|communication', 'Kesabaran|patience', 'Kreativitas|creativity|kreatif', 'Kepemimpinan|leadership', 'Empati|empathy'],
        ],
    ];

    /** Kata kunci umum bila jabatan tidak cocok dengan rumpun mana pun dan bukan karier di platform. */
    private const GENERIC = [
        'label' => 'Umum',
        'hard' => ['Microsoft Office|ms office', 'Microsoft Excel|excel', 'Laporan|reporting', 'Presentasi|presentation', 'Manajemen proyek|project management', 'Analisis|analysis', 'Bahasa Inggris|english', 'Administrasi|administration'],
        'soft' => ['Komunikasi|communication', 'Kerja sama tim|teamwork', 'Problem Solving|pemecahan masalah', 'Kepemimpinan|leadership', 'Manajemen waktu|time management', 'Inisiatif|initiative'],
    ];

    private const HARD = 3;

    private const SOFT = 1;

    /** Rumpun terdekat untuk nama jabatan (kunci), atau null. */
    public function familyFor(string $role): ?string
    {
        $role = mb_strtolower(trim($role));
        if ($role === '') {
            return null;
        }
        $best = null;
        $bestLen = 0;
        foreach (self::FAMILIES as $key => $f) {
            if (preg_match_all('/(?:'.$f['match'].')/iu', $role, $m) && $m[0]) {
                $len = mb_strlen(implode('', $m[0])); // jabatan yang paling banyak cocok menang ("data analyst" mengalahkan "analyst" umum)
                if ($len > $bestLen) {
                    $best = $key;
                    $bestLen = $len;
                }
            }
        }

        return $best;
    }

    /** Daftar jabatan contoh untuk isian (datalist) di form. */
    public function suggestions(): array
    {
        $out = [];
        foreach (self::FAMILIES as $f) {
            $out[] = $f['label'];
        }
        $out = array_merge($out, ['Backend Developer', 'Frontend Developer', 'Data Analyst', 'UI/UX Designer', 'Digital Marketing', 'HR Recruiter', 'Staff Akuntansi', 'Staff Admin', 'Customer Service', 'Sales Executive', 'Project Manager', 'IT Support', 'SOC Analyst']);

        return array_values(array_unique($out));
    }

    /**
     * Daftar kata kunci untuk satu jabatan.
     *
     * @return array{family:?string,family_label:string,keywords:array<int,array{label:string,terms:array<int,string>,group:string,weight:int}>}
     */
    public function keywordsFor(string $role): array
    {
        $key = $this->familyFor($role);
        $def = $key ? self::FAMILIES[$key] : self::GENERIC;

        $list = [];
        foreach (['hard' => self::HARD, 'soft' => self::SOFT] as $group => $weight) {
            foreach ($def[$group] as $raw) {
                $parts = array_values(array_filter(array_map('trim', explode('|', $raw))));
                $list[mb_strtolower($parts[0])] = ['label' => $parts[0], 'terms' => $parts, 'group' => $group, 'weight' => $weight];
            }
        }

        // Skill karier di platform yang namanya cocok dengan jabatan ikut jadi kata kunci keahlian.
        foreach ($this->platformSkills($role) as $skill) {
            $k = mb_strtolower($skill['name']);
            if (! isset($list[$k])) {
                $list[$k] = ['label' => $skill['name'], 'terms' => array_merge([$skill['name']], $skill['aliases']), 'group' => 'hard', 'weight' => self::HARD];
            }
        }

        return ['family' => $key, 'family_label' => $def['label'], 'keywords' => array_values($list)];
    }

    /** @return array<int,array{name:string,aliases:array<int,string>}> */
    private function platformSkills(string $role): array
    {
        $role = mb_strtolower(trim($role));
        if (mb_strlen($role) < 3) {
            return [];
        }

        try {
            $career = Career::listed()->with('skills')->get()->first(function (Career $c) use ($role) {
                $name = mb_strtolower($c->name);

                return $name === $role || str_contains($name, $role) || str_contains($role, $name);
            });
        } catch (\Throwable) {
            return []; // tabel karier tidak tersedia: pakai kamus bawaan saja
        }

        return $career ? $career->skills->map(fn ($s) => ['name' => $s->name, 'aliases' => array_values($s->aliases ?? [])])->all() : [];
    }

    /** Apakah salah satu istilah muncul sebagai kata utuh di teks (huruf kecil). */
    public function has(string $low, array $terms): bool
    {
        foreach ($terms as $term) {
            $term = mb_strtolower(trim($term));
            if ($term === '') {
                continue;
            }
            if (preg_match('/(?<![\p{L}\p{N}+#])'.preg_quote($term, '/').'(?![\p{L}\p{N}+#])/u', $low)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nilai kesiapan ATS satu CV untuk satu jabatan.
     *
     * @param  array  $insights  hasil CvInsights::analyze (bagian, kontak, pengalaman, metrik)
     * @return array<string,mixed>
     */
    public function analyze(string $text, string $role, array $insights): array
    {
        $role = trim(mb_substr($role, 0, 80));
        $low = mb_strtolower($text);
        $set = $this->keywordsFor($role);

        $found = [];
        $missing = [];
        $earned = 0;
        $total = 0;
        foreach ($set['keywords'] as $k) {
            $total += $k['weight'];
            if ($this->has($low, $k['terms'])) {
                $found[] = ['label' => $k['label'], 'group' => $k['group']];
                $earned += $k['weight'];
            } else {
                $missing[] = ['label' => $k['label'], 'group' => $k['group']];
            }
        }
        $coverage = $total > 0 ? (int) round($earned / $total * 100) : 0;

        $checks = $this->checks($text, $low, $role, $set, $insights);
        $checkMax = array_sum(array_column($checks, 'weight'));
        $checkGot = array_sum(array_map(fn ($c) => $c['ok'] ? $c['weight'] : 0, $checks));
        $formatPct = $checkMax > 0 ? $checkGot / $checkMax : 0;

        $score = (int) round($coverage * 0.55 + $formatPct * 100 * 0.45);
        $score = max(0, min(100, $score));

        return [
            'role' => $role,
            'family' => $set['family'],
            'family_label' => $set['family_label'],
            'score' => $score,
            'grade' => match (true) {
                $score >= 80 => 'Siap dibaca ATS',
                $score >= 60 => 'Cukup siap',
                $score >= 40 => 'Perlu penyesuaian',
                default => 'Belum siap ATS',
            },
            'coverage' => $coverage,
            'found' => $found,
            'missing' => $missing,
            'checks' => $checks,
            'keyword_total' => count($set['keywords']),
        ];
    }

    /** Pemeriksaan format yang memengaruhi keterbacaan ATS. 'weight' = bobot pemeriksaan. */
    private function checks(string $text, string $low, string $role, array $set, array $in): array
    {
        $sec = $in['sections'] ?? [];
        $found = fn (string $k) => (bool) ($sec[$k]['found'] ?? false);
        $contact = $in['contact'] ?? [];
        $m = $in['metrics'] ?? ['words' => 0, 'bullets' => 0, 'numbers' => 0, 'verbs' => 0];
        $exp = $in['experience'] ?? ['ranges' => 0];
        $words = (int) ($m['words'] ?? 0);

        // Judul bagian standar: ATS mencari kata baku seperti Pendidikan, Pengalaman, Keahlian.
        $std = $found('pendidikan') && ($found('pengalaman') || $found('proyek')) && $found('skill');
        $roleWords = array_filter(preg_split('/[\s\/,\-]+/u', mb_strtolower($role)) ?: [], fn ($w) => mb_strlen($w) >= 3);
        $roleHit = $roleWords && ($this->has($low, [mb_strtolower($role)]) || count(array_filter($roleWords, fn ($w) => $this->has($low, [$w]))) >= max(1, (int) ceil(count($roleWords) / 2)));

        // Karakter aneh di hasil baca = tanda tabel, kolom, ikon, atau font khusus yang sering merusak ATS.
        $all = mb_substr($text, 0, 6000);
        $odd = preg_match_all('/[^\p{L}\p{N}\s.,:;()\/@+#%&\'"\-–—•*\[\]!?_=<>]/u', $all);
        $oddRatio = $odd / max(1, mb_strlen($all));

        $skillSection = $found('skill');
        $c = [];
        $c[] = ['key' => 'heading', 'ok' => $std, 'weight' => 3, 'label' => 'Judul bagian baku', 'ok_text' => 'Pendidikan, Pengalaman/Proyek, dan Keahlian terbaca sebagai bagian terpisah.', 'tip' => 'Pakai judul baku: Ringkasan, Pendidikan, Pengalaman, Proyek, Keahlian. Hindari judul kreatif seperti "Perjalananku".'];
        $c[] = ['key' => 'contact', 'ok' => ($contact['email'] ?? false) && ($contact['phone'] ?? false), 'weight' => 3, 'label' => 'Kontak berupa teks', 'ok_text' => 'Email dan nomor telepon terbaca sebagai teks biasa.', 'tip' => 'Tulis email dan nomor telepon sebagai teks di badan CV, bukan di header/footer, gambar, atau ikon.'];
        $c[] = ['key' => 'role', 'ok' => (bool) $roleHit, 'weight' => 2, 'label' => 'Jabatan tujuan tercantum', 'ok_text' => 'Nama jabatan tujuan muncul di CV.', 'tip' => 'Cantumkan jabatan tujuan ("'.$role.'") di judul atau ringkasan profil agar ATS langsung menghubungkannya dengan lowongan.'];
        $c[] = ['key' => 'dates', 'ok' => ! $found('pengalaman') || ($exp['ranges'] ?? 0) > 0, 'weight' => 2, 'label' => 'Format tanggal terbaca', 'ok_text' => 'Periode pengalaman terbaca dengan benar.', 'tip' => 'Tulis periode dengan format "Jan 2023 - Des 2024" atau "2022 - sekarang". ATS menghitung lama pengalaman dari tanggal ini.'];
        $c[] = ['key' => 'plain', 'ok' => $oddRatio < 0.03, 'weight' => 3, 'label' => 'Teks bersih tanpa elemen desain', 'ok_text' => 'Tidak ada tanda tabel, kolom, atau ikon yang merusak hasil baca.', 'tip' => 'Banyak simbol aneh terbaca. Hindari tabel, dua kolom, ikon, bilah skill bergambar, dan foto. Pakai satu kolom dengan font standar.'];
        $c[] = ['key' => 'bullets', 'ok' => ($m['bullets'] ?? 0) >= 3, 'weight' => 1, 'label' => 'Poin berbutir sederhana', 'ok_text' => 'Pengalaman ditulis dalam poin yang mudah dipindai.', 'tip' => 'Tulis pencapaian dalam poin dengan simbol sederhana (• atau -), satu baris satu pencapaian.'];
        $c[] = ['key' => 'length', 'ok' => $words >= 250 && $words <= 900, 'weight' => 1, 'label' => 'Panjang wajar (1-2 halaman)', 'ok_text' => 'Panjang CV pas untuk dibaca ATS dan rekruter.', 'tip' => $words < 250 ? 'CV terlalu singkat. Tambahkan pengalaman, proyek, dan keahlian yang relevan dengan jabatan tujuan.' : 'CV terlalu panjang. Ringkas menjadi 1 sampai 2 halaman dan buang yang tidak relevan.'];
        $c[] = ['key' => 'skills', 'ok' => $skillSection, 'weight' => 2, 'label' => 'Bagian keahlian tersendiri', 'ok_text' => 'Ada bagian Keahlian yang mudah dipindai kata kuncinya.', 'tip' => 'Buat bagian "Keahlian" berisi daftar skill dengan penulisan baku (mis. "Microsoft Excel", "SQL") yang sama dengan lowongan.'];

        return $c;
    }
}
