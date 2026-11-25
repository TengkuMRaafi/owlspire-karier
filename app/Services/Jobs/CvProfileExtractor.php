<?php

namespace App\Services\Jobs;

/** Membaca posisi dan skill dari teks CV tanpa AI (port dari cv_parser.py job_finder). */
class CvProfileExtractor
{
    public const SKILL_BANK = [
        'python', 'java', 'javascript', 'typescript', 'php', 'laravel', 'flask', 'django', 'react', 'vue', 'node.js', 'sql',
        'mysql', 'postgresql', 'mongodb', 'docker', 'kubernetes', 'git', 'linux', 'aws', 'machine learning', 'deep learning',
        'nlp', 'tensorflow', 'pytorch', 'data analysis', 'power bi', 'tableau', 'excel', 'flutter', 'android', 'ui/ux', 'figma',
        'cybersecurity', 'networking', 'autocad', 'solidworks', 'catia', 'ansys', 'matlab', 'plc', 'scada', 'cnc', 'welding',
        'hvac', 'maintenance', 'quality control', 'lean', 'k3', 'hse', 'project management', 'revit', 'sketchup', 'surveying',
        'accounting', 'akuntansi', 'audit', 'pajak', 'sap', 'digital marketing', 'seo', 'content writing', 'copywriting',
        'social media', 'sales', 'negotiation', 'customer service', 'recruitment', 'payroll', 'supply chain', 'logistics',
        'procurement', 'inventory', 'english', 'bahasa inggris', 'public speaking', 'teaching', 'research', 'administrasi', 'ms office',
    ];

    private const HEAD = '(?:technical skills?|key skills?|skills?|core competenc\w+|competenc\w+|keahlian|kemampuan|keterampilan|tools?(?: *(?:&|and|dan) *technologies)?|technologies|software|bahasa pemrograman)';

    private const OTHER = '(?:work |professional )?(?:experience|pengalaman(?: kerja| organisasi)?|education|pendidikan|projects?|proyek|certifications?|sertifikasi|sertifikat|organi[sz]ations?|organisasi|summary|ringkasan|profile|profil|about me|tentang saya|references?|referensi|awards?|prestasi|achievements?|languages?|contact|kontak|interests?|hobi|training|pelatihan|personal (?:data|details)|data pribadi)';

    private const NOISE = ['and', 'dan', 'or', 'atau', 'etc', 'dll', 'the', 'yang', 'with', 'dengan', 'basic', 'advanced', 'intermediate', 'beginner', 'good', 'baik', 'mahir', 'dasar', 'menengah', 'skills', 'skill'];

    private const TITLE_WORDS = 'engineer|developer|programmer|analyst|designer|administrator|accountant|technician|specialist|manager|officer|supervisor|coordinator|consultant|assistant|executive|operator|architect|scientist|researcher|teacher|instructor|auditor|marketer|writer|editor|drafter|surveyor|inspector|staff|intern|akuntan|teknisi|insinyur|arsitek|guru|dosen|perawat|admin';

    private const STRONG = ['engineer', 'developer', 'programmer', 'analyst', 'designer', 'accountant', 'technician', 'akuntan', 'teknisi', 'insinyur', 'arsitek'];

    private const LEAD_STOP = ['as', 'a', 'an', 'the', 'and', 'at', 'in', 'of', 'for', 'to', 'with', 'sebagai', 'di', 'pada', 'dan', 'yang', 'saya', 'was', 'is', 'were', 'be', 'worked', 'working', 'bekerja', 'my', 'our', 'his', 'her', 'become', 'menjadi', 'seorang', 'calon', 'future', 'aspiring', 'experienced', 'berpengalaman'];

    /** Skill dari kamus yang muncul di teks. */
    public static function bankSkills(string $text): array
    {
        $low = mb_strtolower($text);

        return array_values(array_filter(self::SKILL_BANK, fn ($s) => preg_match('/(?<![a-z0-9])'.preg_quote($s, '/').'(?![a-z0-9])/u', $low)));
    }

    /** @return array{positions: string[], skills: string[]} */
    public function extract(string $text): array
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $skills = [];
        foreach (array_merge($this->sectionSkills($text), self::bankSkills($text)) as $s) {
            if (! in_array(mb_strtolower($s), array_map('mb_strtolower', $skills), true)) {
                $skills[] = $s;
            }
        }

        return ['positions' => $this->titles($text), 'skills' => array_slice($skills, 0, 25)];
    }

    private function splitItems(string $line): array
    {
        if (str_contains($line, ':')) {
            $line = explode(':', $line, 2)[1];
        }
        $out = [];
        foreach (preg_split('/[,;•·▪●◦►|\n]|\s{2,}|\s[-–]\s/u', $line) as $x) {
            $x = trim($x, " \t-*–—.()");
            if (mb_strlen($x) >= 2 && mb_strlen($x) <= 30 && count(preg_split('/\s+/', $x)) <= 4 && preg_match('/[A-Za-z]/', $x) && ! in_array(mb_strtolower($x), self::NOISE, true)) {
                $out[] = mb_strtolower($x);
            }
        }

        return $out;
    }

    private function sectionSkills(string $text): array
    {
        $lines = array_map('trim', preg_split('/\R/u', $text));
        $found = [];
        foreach ($lines as $i => $line) {
            $isOther = (bool) preg_match('/^\W*'.self::OTHER.'\W*$/iu', $line);
            if (! $isOther && preg_match('/^\W*'.self::HEAD.'\s*[:\-–]\s*(.+)$/iu', $line, $m)) {
                $found = array_merge($found, $this->splitItems($m[1]));
            } elseif (preg_match('/^\W*'.self::HEAD.'\W*$/iu', $line)) {
                foreach (array_slice($lines, $i + 1, 15) as $l) {
                    if ($l === '') {
                        continue;
                    }
                    if (preg_match('/^\W*'.self::OTHER.'\W*$/iu', $l) || (mb_strtoupper($l) === $l && mb_strlen($l) < 30 && count(preg_split('/\s+/', $l)) <= 3)) {
                        break;
                    }
                    $found = array_merge($found, $this->splitItems($l));
                }
            }
        }

        return $found;
    }

    private function titles(string $text): array
    {
        $lines = array_slice(array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)))), 0, 70);
        $cnt = [];
        $shown = [];
        foreach ($lines as $n => $line) {
            if (mb_strlen($line) > 140) {
                continue;
            }
            preg_match_all('/\b((?:[A-Za-z][A-Za-z+#.\/&-]*\s+){0,2}(?:'.self::TITLE_WORDS.'))\b/iu', $line, $ms);
            foreach ($ms[1] as $match) {
                $words = preg_split('/\s+/', $match);
                while (count($words) > 1 && in_array(mb_strtolower($words[0]), self::LEAD_STOP, true)) {
                    array_shift($words);
                }
                if (count($words) < 2 && ! in_array(mb_strtolower($words[0]), self::STRONG, true)) {
                    continue;
                }
                $t = implode(' ', $words);
                $t = preg_match('/\p{Lu}/u', $t) ? $t : mb_convert_case($t, MB_CASE_TITLE);
                $key = mb_strtolower($t);
                $shown[$key] ??= $t;
                $cnt[$key] = ($cnt[$key] ?? 0) + ($n < 8 ? 3 : 1);
            }
        }
        $keys = array_keys($shown);
        usort($keys, fn ($a, $b) => [$cnt[$b], array_search($a, array_keys($shown))] <=> [$cnt[$a], array_search($b, array_keys($shown))]);

        return array_map(fn ($k) => $shown[$k], array_slice($keys, 0, 4));
    }
}
