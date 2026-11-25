<?php

namespace App\Services\Careers;

use App\Models\CvAnalysis;
use App\Models\CvProfile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Membaca teks CV (PDF, DOCX, TXT, atau gambar/PDF hasil scan lewat OCR), mendeteksi skill dari kamus,
 * menilai kualitas CV (CvInsights), lalu mencocokkan dengan karier dan menyimpan riwayat analisisnya.
 */
class CvAnalyzer
{
    public const HISTORY_LIMIT = 12;

    private const MAX_DOCX_XML = 8 * 1024 * 1024; // batas setelah dekompresi, melindungi dari berkas ZIP "bom"

    public function __construct(private CareerMatcher $matcher, private CvInsights $insights, private CvAtsKeywords $ats)
    {
    }

    /** OCR tersedia bila tesseract (dan untuk PDF scan, pdftoppm) terpasang di server. */
    public function ocrAvailable(): bool
    {
        $finder = new ExecutableFinder();

        return $finder->find('tesseract') !== null;
    }

    public function extractText(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $path = $file->getRealPath();

        return match ($ext) {
            'txt', 'md' => (string) file_get_contents($path),
            'docx' => $this->docx($path),
            'pdf' => $this->pdf($path),
            'png', 'jpg', 'jpeg' => $this->ocrImage($path),
            default => throw new \InvalidArgumentException('Format belum didukung. Pakai PDF, DOCX, TXT, atau gambar (PNG/JPG).'),
        };
    }

    private function docx(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \InvalidArgumentException('Berkas DOCX tidak bisa dibaca.');
        }
        $stat = $zip->statName('word/document.xml');
        if ($stat === false) {
            $zip->close();
            throw new \InvalidArgumentException('Berkas DOCX tidak bisa dibaca.');
        }
        if (($stat['size'] ?? 0) > self::MAX_DOCX_XML) {
            $zip->close();
            throw new \InvalidArgumentException('Isi berkas DOCX terlalu besar untuk dianalisis. Pakai versi PDF atau tempel teksnya.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new \InvalidArgumentException('Berkas DOCX tidak bisa dibaca.');
        }
        $xml = preg_replace('#</w:p>|<w:br[^>]*/>|<w:tab/>#', "\n", $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function pdf(string $path): string
    {
        $text = '';
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $text = (new \Smalot\PdfParser\Parser())->parseFile($path)->getText();
            } catch (\Throwable) {
                $text = '';
            }
        }

        if (trim($text) === '') {
            // Cadangan: poppler-utils (apt install poppler-utils).
            $p = new Process(['pdftotext', '-layout', $path, '-']);
            $p->setTimeout(20);
            try {
                $p->run();
                $text = $p->isSuccessful() ? $p->getOutput() : '';
            } catch (\Throwable) {
                $text = '';
            }
        }

        if (mb_strlen(trim($text)) >= 40) {
            return $text;
        }

        // Teks kosong biasanya berarti PDF hasil scan (gambar): coba OCR bila tersedia.
        if ($this->ocrAvailable() && ($ocr = $this->ocrPdf($path)) !== '') {
            return $ocr;
        }

        throw new \InvalidArgumentException('PDF ini tidak mengandung teks (kemungkinan hasil scan) atau belum bisa dibaca di server ini. Unggah DOCX/TXT, tempel teks CV, atau minta admin memasang tesseract-ocr dan poppler-utils.');
    }

    private function ocrPdf(string $path): string
    {
        $dir = sys_get_temp_dir().'/cvocr-'.bin2hex(random_bytes(6));
        if (! @mkdir($dir, 0700)) {
            return '';
        }

        try {
            $p = new Process(['pdftoppm', '-r', '170', '-png', '-l', '3', $path, $dir.'/p']);
            $p->setTimeout(45);
            $p->run();
            $out = '';
            foreach (glob($dir.'/p*.png') ?: [] as $png) {
                $out .= $this->ocrImage($png, false)."\n";
            }

            return trim($out);
        } catch (\Throwable) {
            return '';
        } finally {
            foreach (glob($dir.'/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($dir);
        }
    }

    private function ocrImage(string $path, bool $strict = true): string
    {
        if (! $this->ocrAvailable()) {
            if ($strict) {
                throw new \InvalidArgumentException('Pembacaan gambar (OCR) belum aktif di server ini. Unggah PDF/DOCX/TXT atau tempel teks CV.');
            }

            return '';
        }

        foreach (['ind+eng', 'eng'] as $lang) { // bahasa Indonesia bila datanya terpasang, selain itu Inggris
            $p = new Process(['tesseract', $path, 'stdout', '-l', $lang, '--psm', '6']);
            $p->setTimeout(40);
            try {
                $p->run();
                if ($p->isSuccessful() && trim($p->getOutput()) !== '') {
                    return $p->getOutput();
                }
            } catch (\Throwable) {
                // coba bahasa berikutnya
            }
        }

        if ($strict) {
            throw new \InvalidArgumentException('Teks di gambar tidak terbaca. Pakai foto/scan yang lebih jelas atau tempel teks CV.');
        }

        return '';
    }

    /** @return array<int,Skill> skill yang ditemukan di teks. */
    public function detectSkills(string $text): array
    {
        $low = mb_strtolower($text);
        $found = [];

        foreach (Skill::all() as $skill) {
            foreach (array_merge([$skill->name], $skill->aliases ?? []) as $term) {
                $term = mb_strtolower(trim($term));
                if ($term !== '' && preg_match('/(?<![\p{L}\p{N}+#])'.preg_quote($term, '/').'(?![\p{L}\p{N}+#])/u', $low)) {
                    $found[$skill->id] = $skill;
                    break;
                }
            }
        }

        return array_values($found);
    }

    /**
     * @param  string  $mode  'standard' (CV biasa) atau 'ats' (CV ramah ATS, dinilai untuk $role)
     */
    public function analyzeAndSave(User $user, string $text, ?string $label = null, string $mode = 'standard', ?string $role = null): CvProfile
    {
        $text = trim(mb_substr($text, 0, 40000));
        $mode = $mode === 'ats' && trim((string) $role) !== '' ? 'ats' : 'standard';
        $skills = $this->detectSkills($text);
        $ids = array_map(fn ($s) => $s->id, $skills);
        $names = array_map(fn ($s) => $s->name, $skills);

        $insights = $this->insights->analyze($text, $names);
        $ats = $mode === 'ats' ? $this->ats->analyze($text, (string) $role, $insights) : null;
        $matches = $this->matcher->rank($ids)->map(fn ($m) => [
            'career_id' => $m['career']->id, 'score' => $m['score'], 'matched' => $m['matched'], 'missing' => $m['missing'],
        ])->values()->all();

        return DB::transaction(function () use ($user, $text, $skills, $ids, $names, $matches, $insights, $label, $mode, $ats) {
            // Skill dari CV masuk ke profil tanpa menimpa level yang sudah diatur sendiri; yang tidak lagi ada di CV baru dilepas.
            foreach ($ids as $id) {
                DB::table('user_skills')->insertOrIgnore(['user_id' => $user->id, 'skill_id' => $id, 'level' => 1, 'source' => 'cv', 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('user_skills')->where('user_id', $user->id)->where('source', 'cv')->whereNotIn('skill_id', $ids ?: [0])->delete();

            $profile = CvProfile::updateOrCreate(['user_id' => $user->id], [
                'raw_text' => $text,
                'parsed' => [
                    'skills' => array_map(fn ($s) => ['id' => $s->id, 'name' => $s->name], $skills),
                    'matches' => $matches,
                    'insights' => $insights,
                    'mode' => $mode,
                    'ats' => $ats,
                    'analyzed_at' => now()->toIso8601String(),
                ],
            ]);

            CvAnalysis::create([
                'user_id' => $user->id,
                'label' => $label !== null && trim($label) !== '' ? mb_substr(trim($label), 0, 80) : null,
                'score' => $insights['score'],
                'result' => [
                    'grade' => $insights['grade'],
                    'parts' => array_map(fn ($p) => ['label' => $p['label'], 'score' => $p['score'], 'max' => $p['max']], array_values($insights['parts'])),
                    'skills' => $names,
                    'top_matches' => array_slice(array_map(fn ($m) => ['career_id' => $m['career_id'], 'score' => $m['score']], $matches), 0, 3),
                    'words' => $insights['metrics']['words'],
                    'years' => $insights['experience']['years'],
                    'issues' => count($insights['issues']),
                    'mode' => $mode,
                    'role' => $ats['role'] ?? null,
                    'ats_score' => $ats['score'] ?? null,
                    'ats_coverage' => $ats['coverage'] ?? null,
                ],
            ]);
            $old = CvAnalysis::where('user_id', $user->id)->orderByDesc('id')->pluck('id')->slice(self::HISTORY_LIMIT);
            if ($old->isNotEmpty()) {
                CvAnalysis::whereIn('id', $old)->delete();
            }

            return $profile;
        });
    }

    /** Hapus semua jejak CV pengguna: teks, hasil, riwayat, dan skill yang datang dari CV. */
    public function purge(User $user): void
    {
        DB::transaction(function () use ($user) {
            CvProfile::where('user_id', $user->id)->delete();
            CvAnalysis::where('user_id', $user->id)->delete();
            DB::table('user_skills')->where('user_id', $user->id)->where('source', 'cv')->delete();
        });
        app(CvVisual::class)->forget($user->id); // gambar halaman CV dan posisi barisnya
    }
}
