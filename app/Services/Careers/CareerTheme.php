<?php

namespace App\Services\Careers;

use App\Models\Career;

/** Memilih tema gambar latar kartu karier dari kata kunci */
class CareerTheme
{
    /** tema => [label, kata kunci]. */
    public const THEMES = [
        'it' => ['IT & Teknologi', ['software', 'developer', 'programmer', 'coding', 'web', 'ai', 'machine learning', 'data', 'siber', 'cyber', 'security', 'soc', 'siem', 'cloud', 'devops', 'jaringan', 'network', 'it', 'informatika', 'aplikasi', 'database', 'backend', 'frontend', 'llm', 'nlp']],
        'uang' => ['Keuangan', ['akuntan', 'akuntansi', 'accounting', 'pajak', 'perpajakan', 'tax', 'keuangan', 'finance', 'financial', 'audit', 'auditor', 'bank', 'perbankan', 'investasi', 'treasury', 'kasir', 'laporan keuangan', 'budget', 'anggaran', 'asuransi']],
        'mesin' => ['Mesin & Industri', ['mesin', 'mekanik', 'mekanikal', 'mechanical', 'manufaktur', 'manufacturing', 'produksi', 'industri', 'industrial', 'otomotif', 'maintenance', 'perawatan', 'teknisi', 'pabrik', 'elektro', 'mekatronika', 'lean', 'time study']],
        'k3' => ['K3 & Keselamatan', ['k3', 'ehs', 'hse', 'keselamatan', 'safety', 'hiradc', 'risk assessment']],
        'logistik' => ['Logistik', ['logistik', 'logistic', 'logistics', 'supply chain', 'rantai pasok', 'procurement', 'pengadaan', 'purchasing', 'gudang', 'warehouse', 'import', 'impor', 'ekspor', 'export', 'kepabeanan', 'customs', 'distribusi', 'inventory', 'kurir', 'pengiriman']],
        'pendidikan' => ['Pendidikan', ['pendidikan', 'pelatihan', 'training', 'learning', 'guru', 'dosen', 'pengajar', 'instruktur', 'edukasi', 'education', 'kurikulum', 'sekolah']],
        'bisnis' => ['Bisnis & Sales', ['sales', 'penjualan', 'business development', 'partnership', 'kemitraan', 'bisnis', 'business', 'negosiasi', 'account executive', 'akuisisi']],
        'desain' => ['Desain', ['desain', 'design', 'designer', 'ui', 'ux', 'kreatif', 'creative', 'grafis', 'ilustrasi', 'animator', 'multimedia', 'arsitek', 'interior']],
        'marketing' => ['Marketing', ['marketing', 'pemasaran', 'konten', 'content', 'copywriter', 'seo', 'media sosial', 'social media', 'brand', 'komunikasi', 'humas', 'public relation', 'jurnalis', 'periklanan', 'iklan']],
        'sdm' => ['SDM', ['hr', 'hrd', 'sdm', 'talent', 'rekrutmen', 'recruitment', 'recruiter', 'ketenagakerjaan', 'payroll', 'people', 'human resource', 'sumber daya manusia']],
        'cs' => ['Layanan Pelanggan', ['customer', 'pelanggan', 'cs', 'call center', 'contact center', 'helpdesk', 'help desk', 'layanan', 'service', 'support', 'frontliner']],
        'kesehatan' => ['Kesehatan', ['kesehatan', 'medis', 'medical', 'perawat', 'dokter', 'apoteker', 'farmasi', 'klinik', 'rumah sakit', 'bidan', 'gizi', 'health']],
        'konstruksi' => ['Konstruksi', ['konstruksi', 'sipil', 'civil', 'bangunan', 'gedung', 'proyek', 'survey', 'surveyor', 'estimator', 'teknik sipil', 'infrastruktur']],
        'hukum' => ['Hukum', ['hukum', 'legal', 'law', 'paralegal', 'advokat', 'notaris', 'kepatuhan', 'compliance', 'kontrak']],
        'pertanian' => ['Pertanian', ['pertanian', 'agro', 'agronomi', 'perkebunan', 'kehutanan', 'perikanan', 'peternakan', 'agribisnis', 'pangan']],
        'kuliner' => ['Kuliner & Hospitality', ['kuliner', 'chef', 'koki', 'barista', 'restoran', 'hotel', 'hospitality', 'perhotelan', 'pariwisata', 'tourism', 'catering', 'f&b']],
    ];

    public const DEFAULT = 'umum';

    /** @return array{key:string,label:string,image:string,is_photo:bool} */
    public function for(Career $career): array
    {
        $key = $this->detect($career);
        $label = self::THEMES[$key][0] ?? 'Karier';

        $custom = $career->detail('image');
        if (is_string($custom) && preg_match('#^(https?://|/)#', $custom)) {
            return ['key' => $key, 'label' => $label, 'image' => $custom, 'is_photo' => true];
        }

        foreach (['jpg', 'jpeg', 'webp', 'png'] as $ext) {
            if (is_file(public_path("images/karier/{$key}.{$ext}"))) {
                return ['key' => $key, 'label' => $label, 'image' => asset("images/karier/{$key}.{$ext}"), 'is_photo' => true];
            }
        }

        return ['key' => $key, 'label' => $label, 'image' => asset("images/karier/{$key}.svg"), 'is_photo' => false];
    }

    /** Tema dengan skor kata kunci tertinggi. Nama bobot 4, tag 3, divisi 3, ringkasan 1. */
    public function detect(Career $career): string
    {
        $fields = [
            [4, [$career->name, $career->slug]],
            [3, (array) $career->detail('tags', [])],
            [3, [$career->division]],
            [1, [$career->summary]],
        ];

        $best = self::DEFAULT;
        $bestScore = 0;

        foreach (self::THEMES as $key => [, $keywords]) {
            $score = 0;
            foreach ($fields as [$weight, $texts]) {
                $hay = ' '.$this->normalize(implode(' ', array_filter(array_map('strval', $texts)))).' ';
                foreach ($keywords as $kw) {
                    if ($this->contains($hay, $this->normalize($kw))) {
                        $score += $weight;
                    }
                }
            }
            if ($score > $bestScore) {
                $best = $key;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);

        return trim(preg_replace('/[^a-z0-9&]+/u', ' ', $s) ?? $s);
    }

    private function contains(string $hay, string $kw): bool
    {
        if ($kw === '') {
            return false;
        }

        // Kata pendek (it, ai, hr, ui...) harus utuh; kata panjang boleh berimbuhan di belakang.
        $needle = mb_strlen($kw) <= 3 ? " {$kw} " : " {$kw}";

        return str_contains($hay, $needle);
    }
}
