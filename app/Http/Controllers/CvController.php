<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\CvAnalysis;
use App\Models\CvProfile;
use App\Services\Ai\AiQuota;
use App\Services\Billing\EntitlementService;
use App\Services\Careers\CvAnalyzer;
use App\Services\Careers\CvAtsKeywords;
use App\Services\Careers\CvInsights;
use App\Services\Careers\CvJobMatcher;
use App\Services\Careers\CvLayout;
use App\Services\Careers\CvVisual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Analisis CV: khusus Premium. Pengguna gratis melihat penjelasan dan tombol upgrade. */
class CvController extends Controller
{
    public function __construct(private EntitlementService $entitlements, private CvAnalyzer $analyzer, private CvInsights $insights, private CvLayout $layout, private CvVisual $visuals, private AiQuota $quota, private CvAtsKeywords $atsKeywords)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $premium = $this->entitlements->isPremium($user->id);
        $data = $this->data($user->id);

        // Pratinjau CV bertanda panah: hanya dihitung untuk Premium yang sudah punya hasil analisis.
        $preview = null;
        if ($premium && $data['profile'] && $data['insights']) {
            $first = $data['top']->first();
            $preview = $this->insights->preview(
                (string) $data['profile']->raw_text,
                $data['insights'],
                $data['skills']->pluck('name')->all(),
                $first ? $first['career']->name : null,
                $first ? array_slice($first['missing'] ?? [], 0, 4) : [],
                $data['ats'], // mode ATS: catatan kata kunci jabatan tujuan; null untuk CV biasa
            );
            $preview = $this->withDocument($preview, $user->id);
        }

        return view('careers.cv', ['premium' => $premium] + $data + [
            'ocr' => $this->analyzer->ocrAvailable(),
            'preview' => $preview,
            'quotaLeft' => $this->quota->remaining($user, 'cv_analysis'),
            'quotaLimit' => $this->quota->limit($user, 'cv_analysis'),
            'roleSuggestions' => $this->atsKeywords->suggestions(),
        ]);
    }

    /** Gambar halaman CV asli untuk pratinjau (terenkripsi di disk, hanya bisa dilihat pemiliknya). */
    public function page(Request $request, int $n)
    {
        abort_unless($this->entitlements->isPremium($request->user()->id), 403);
        $bin = $this->visuals->image($request->user()->id, $n);
        abort_if($bin === null, 404);

        return response($bin, 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Versi cetak (Simpan sebagai PDF lewat dialog cetak browser). */
    public function report(Request $request)
    {
        abort_unless($this->entitlements->isPremium($request->user()->id), 403);
        $data = $this->data($request->user()->id);
        abort_unless($data['profile'], 404);

        return view('careers.cv-report', $data + ['user' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->entitlements->isPremium($request->user()->id), 403);

        $mimes = $this->analyzer->ocrAvailable() ? 'pdf,docx,txt,png,jpg,jpeg' : 'pdf,docx,txt';
        $request->validate([
            'cv_file' => ['nullable', 'file', 'max:4096', "mimes:{$mimes}"],
            'cv_text' => ['nullable', 'string', 'max:40000'],
            'label' => ['nullable', 'string', 'max:80'],
            'cv_type' => ['nullable', 'in:standard,ats'],
            'target_role' => ['nullable', 'required_if:cv_type,ats', 'string', 'min:3', 'max:80'],
            'cv_layout' => ['nullable', 'string', 'max:600000'],
            'cv_pages' => ['nullable', 'array', 'max:'.CvLayout::MAX_PAGES],
            'cv_pages.*' => ['string', 'max:1800000'],
        ], [
            'cv_file.mimes' => $this->analyzer->ocrAvailable() ? 'Berkas harus PDF, DOCX, TXT, atau gambar PNG/JPG.' : 'Berkas harus PDF, DOCX, atau TXT.',
            'cv_file.max' => 'Ukuran berkas maksimal 4 MB.',
            'target_role.required_if' => 'Isi jabatan yang kamu cari untuk analisis CV ATS.',
            'target_role.min' => 'Jabatan minimal 3 huruf, mis. Data Analyst.',
        ]);

        // Jatah harian: dicek setelah validasi (input salah tidak memakan jatah) dan sebelum pekerjaan berat (baca PDF/OCR).
        if ($this->quota->remaining($request->user(), 'cv_analysis') <= 0) {
            return back()->withErrors(['cv_file' => 'Jatah analisis CV hari ini sudah habis ('.$this->quota->limit($request->user(), 'cv_analysis').' kali per hari). Coba lagi besok.']);
        }

        // PDF yang sudah dibaca browser (pdf.js): teksnya diurutkan per kolom (CV dua kolom jadi terbaca benar),
        // dan gambar halamannya dipakai untuk pratinjau CV asli.
        $visual = $this->visualFrom($request);
        $layoutText = null;
        if ($visual && $visual['layout']['total'] <= CvLayout::MAX_PAGES) {
            $ordered = $this->layout->text($this->layout->segments($visual['layout']['pages']));
            $layoutText = mb_strlen(trim($ordered)) >= 40 ? $ordered : null;
        }

        try {
            $text = $request->hasFile('cv_file') ? $this->analyzer->extractText($request->file('cv_file')) : (string) $request->input('cv_text');
        } catch (\InvalidArgumentException $e) {
            if ($layoutText === null) {
                return back()->withErrors(['cv_file' => $e->getMessage()]);
            }
            $text = '';
        }
        if ($layoutText !== null) {
            $text = $layoutText;
        }

        if (mb_strlen(trim($text)) < 40) {
            return back()->withErrors(['cv_text' => 'Teks CV terlalu pendek untuk dianalisis. Unggah berkas atau tempel isi CV.']);
        }

        $before = CvAnalysis::where('user_id', $request->user()->id)->latest('id')->value('score');
        $mode = $request->input('cv_type') === 'ats' ? 'ats' : 'standard';
        $role = $mode === 'ats' ? trim((string) $request->input('target_role')) : null;
        $profile = $this->analyzer->analyzeAndSave($request->user(), $text, $request->input('label'), $mode, $role);
        $this->quota->consume($request->user(), 'cv_analysis'); // hanya analisis yang berhasil tersimpan yang menghabiskan jatah
        try {
            $this->visuals->forget($request->user()->id); // gambar CV sebelumnya tidak boleh tampil di samping analisis yang baru
            if ($visual) {
                $this->visuals->save($request->user()->id, $visual['layout'], $visual['images']);
            }
        } catch (\Throwable $e) {
            report($e); // pratinjau bergambar hanya pelengkap: analisis tetap tersimpan
        }
        $n = count($profile->parsed['skills']);
        $score = $profile->parsed['insights']['score'] ?? null;
        $delta = $before !== null && $score !== null ? $score - $before : null;

        $msg = "CV dianalisis: skor {$score}/100, {$n} skill terbaca".($delta !== null && $delta !== 0 ? ' ('.($delta > 0 ? '+' : '').$delta.' dari analisis sebelumnya)' : '').'.';
        if ($mode === 'ats') {
            $ats = $profile->parsed['ats'] ?? [];
            $msg = "CV ATS untuk {$role} dianalisis: kesiapan ATS ".($ats['score'] ?? 0).'/100, '.($ats['coverage'] ?? 0)."% kata kunci tampil, skor CV {$score}/100.";
        }
        if ($n === 0 && $mode !== 'ats') {
            $msg = "CV dianalisis: skor {$score}/100, tetapi belum ada skill dari kamus kami yang terbaca. Coba tambahkan bagian keahlian.";
        }

        return redirect()->route('cv.index')->with('status', $msg);
    }

    /** Cocokkan CV tersimpan dengan teks lowongan yang ditempel. Hasilnya hanya di sesi, tidak disimpan. */
    public function matchJob(Request $request, CvJobMatcher $matcher): RedirectResponse
    {
        abort_unless($this->entitlements->isPremium($request->user()->id), 403);
        $data = $request->validate(['job' => ['required', 'string', 'min:40', 'max:6000'], 'job_title' => ['nullable', 'string', 'max:120']], [
            'job.required' => 'Tempel teks lowongan yang ingin dicocokkan.', 'job.min' => 'Teks lowongan terlalu pendek. Tempel deskripsi dan persyaratannya.',
        ]);

        $cv = CvProfile::where('user_id', $request->user()->id)->value('raw_text');
        if (mb_strlen(trim((string) $cv)) < 40) {
            return back()->withErrors(['job' => 'Analisis CV dulu sebelum mencocokkan dengan lowongan.'])->withInput();
        }

        return redirect()->to(route('cv.index').'#cocok')->withInput()->with('job_match', $matcher->match((string) $cv, $data['job']) + ['title' => $data['job_title'] ?? null]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->analyzer->purge($request->user());

        return redirect()->route('cv.index')->with('status', 'Data CV kamu sudah dihapus.');
    }

    /**
     * Tata letak dan gambar halaman dari browser, hanya untuk berkas PDF yang diunggah. Null bila tidak ada atau tidak wajar.
     *
     * @return array{layout:array,images:array}|null
     */
    private function visualFrom(Request $request): ?array
    {
        if (! $request->hasFile('cv_file') || strtolower($request->file('cv_file')->getClientOriginalExtension()) !== 'pdf') {
            return null;
        }
        $layout = $this->layout->sanitize($request->input('cv_layout'));
        $images = $this->visuals->decode($request->input('cv_pages'));
        if (! $layout || ! $images || count($images) !== count($layout['pages'])) {
            return null;
        }

        return ['layout' => $layout, 'images' => $images];
    }

    /** Tambahkan gambar halaman dan kotak tiap bagian ke pratinjau, bila CV asli tersimpan. Tanpa itu pratinjau berupa teks. */
    private function withDocument(array $preview, int $userId): array
    {
        $visual = $this->visuals->load($userId);
        if (! $visual) {
            return $preview;
        }

        $blocks = $this->layout->attach($preview['blocks'], $visual['pages']);
        if (! collect($blocks)->contains(fn ($b) => ! empty($b['rects']))) {
            return $preview;
        }

        $preview['blocks'] = $blocks;
        $preview['pages'] = collect($visual['sizes'])->map(fn ($s, $i) => [
            'url' => route('cv.page', ['n' => $i + 1, 'v' => $visual['v']]), 'w' => $s[0], 'h' => $s[1],
        ])->all();

        return $preview;
    }

    /** Data bersama halaman utama dan laporan cetak. */
    private function data(int $userId): array
    {
        $profile = CvProfile::where('user_id', $userId)->first();
        $matches = collect($profile?->parsed['matches'] ?? []);
        $careers = Career::with('course')->whereIn('id', $matches->pluck('career_id'))->get()->keyBy('id');
        $history = CvAnalysis::where('user_id', $userId)->orderBy('id')->get();

        return [
            'profile' => $profile,
            'skills' => collect($profile?->parsed['skills'] ?? []),
            'top' => $matches->take(5)->map(fn ($m) => $m + ['career' => $careers[$m['career_id']] ?? null])->filter(fn ($m) => $m['career'])->values(),
            'insights' => $profile?->parsed['insights'] ?? null,
            'ats' => ($profile?->parsed['mode'] ?? 'standard') === 'ats' ? ($profile->parsed['ats'] ?? null) : null,
            'history' => $history,
            'previous' => $history->count() >= 2 ? $history[$history->count() - 2] : null,
        ];
    }
}
