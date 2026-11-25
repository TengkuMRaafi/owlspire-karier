<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\CvProfile;
use App\Models\JobApplication;
use App\Services\Ai\AiQuota;
use App\Services\Ai\CvWriter;
use App\Services\Careers\CvInsights;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Saran perbaikan CV dan surat lamaran dari CV yang sudah diunggah di menu Analisis CV. */
class CvToolsController extends Controller
{
    public function __construct(private CvWriter $writer, private AiQuota $quota, private CvInsights $insights)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $cv = CvProfile::where('user_id', $user->id)->first();
        $app = $request->query('lamaran') ? JobApplication::where('user_id', $user->id)->find($request->query('lamaran')) : null;

        $result = session('tool_result');

        // Saran AI per bagian digabung ke pratinjau CV yang sama dengan halaman Analisis CV.
        $advice = null;
        $updated = null;
        if ($cv && is_array($result['data'] ?? null) && isset($result['data']['sections'])) {
            $advice = $this->insights->adviceBlocks((string) $cv->raw_text, $result['data']);
            $updated = $this->insights->updatedCv((string) $cv->raw_text, $result['data']);
        }

        return view('account.cvtools', [
            'hasCv' => $cv && mb_strlen(trim((string) $cv->raw_text)) >= 100,
            'result' => $result,
            'advice' => $advice,
            'updated' => $updated,
            'prefill' => ['role' => $app?->title, 'company' => $app?->company],
            'aiOn' => $this->writer->enabled(),
            'left' => ['cv_rewrite' => $this->quota->remaining($user, 'cv_rewrite'), 'cover_letter' => $this->quota->remaining($user, 'cover_letter')],
            'limits' => ['cv_rewrite' => $this->quota->limit($user, 'cv_rewrite'), 'cover_letter' => $this->quota->limit($user, 'cover_letter')],
        ]);
    }

    public function improve(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'max:120'],
            'job' => ['nullable', 'string', 'max:4000'],
        ]);

        // Hasil Analisis CV (skor dan masalah terdeteksi) ikut jadi bahan saran AI, supaya saran fokus ke kelemahan nyata.
        $insights = CvProfile::where('user_id', $request->user()->id)->first()?->parsed['insights'] ?? null;
        $hints = $insights ? 'Skor CV '.$insights['score'].'/100. Masalah terdeteksi: '.collect($insights['issues'])->take(8)->pluck('title')->implode('; ').'.' : '';

        return $this->run($request, 'cv_rewrite', $data, fn (string $cv) => $this->writer->improve($cv, (string) ($data['job'] ?? ''), $data['role'], $hints));
    }

    public function letter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'job' => ['nullable', 'string', 'max:4000'],
            'tone' => ['required', 'in:formal,santai'],
        ]);

        return $this->run($request, 'cover_letter', $data, fn (string $cv) => $this->writer->coverLetter($cv, (string) ($data['job'] ?? ''), $data['role'], (string) ($data['company'] ?? ''), $data['tone']));
    }

    private function run(Request $request, string $feature, array $data, callable $generate): RedirectResponse
    {
        $user = $request->user();
        $cv = CvProfile::where('user_id', $user->id)->value('raw_text');
        if (mb_strlen(trim((string) $cv)) < 100) {
            return back()->withErrors(['role' => 'CV-mu belum ada. Unggah dulu di menu Analisis CV.'])->withInput();
        }
        if (! $this->writer->enabled()) {
            return back()->withErrors(['role' => 'Penulis AI belum aktif di server ini.'])->withInput();
        }
        if ($this->quota->remaining($user, $feature) <= 0) {
            return back()->withErrors(['role' => 'Jatah hari ini untuk alat ini sudah habis. Coba lagi besok atau lihat Premium.'])->withInput();
        }

        $text = $generate((string) $cv);
        if ($text === null) {
            return back()->withErrors(['role' => 'AI sedang sibuk. Coba lagi sebentar lagi; jatahmu tidak terpakai.'])->withInput();
        }
        $this->quota->consume($user, $feature);

        $payload = is_array($text) ? ['data' => $text] : ['text' => $text];

        return back()->withInput()->with('tool_result', ['type' => $feature, 'role' => $data['role']] + $payload);
    }
}
