<?php

namespace App\Http\Controllers;

use App\Jobs\RunJobSearch;
use App\Models\CvProfile;
use App\Services\Billing\EntitlementService;
use App\Services\Careers\CvAnalyzer;
use App\Services\Jobs\CvProfileExtractor;
use App\Services\Jobs\JobFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Pencarian lowongan multi-sumber. Semua hasil dan baca CV terbuka untuk semua pengunjung. */
class JobsController extends Controller
{
    public function __construct(private JobFinder $finder, private EntitlementService $entitlements)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $saved = $user ? CvProfile::where('user_id', $user->id)->first() : null;

        return view('jobs.index', [
            'sources' => $this->finder->catalog(),
            'guest' => ! $user,
            'savedSkills' => array_slice($saved?->parsed['skills'] ?? [], 0, 25),
        ]);
    }

    /** Baca CV (PDF/DOCX/TXT) lalu kembalikan posisi dan skill. */
    public function cv(Request $request, CvAnalyzer $analyzer, CvProfileExtractor $extractor): JsonResponse
    {
        $request->validate(['cv' => ['required', 'file', 'max:4096', 'mimes:pdf,docx,txt']], [
            'cv.mimes' => 'Berkas harus PDF, DOCX, atau TXT.', 'cv.max' => 'Ukuran berkas maksimal 4 MB.',
        ]);

        try {
            $text = $analyzer->extractText($request->file('cv'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        if (mb_strlen(trim($text)) < 30) {
            return response()->json(['error' => 'Teks CV tidak terbaca (mungkin PDF hasil scan).'], 422);
        }

        return response()->json($extractor->extract($text));
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'skills' => ['array', 'max:25'], 'skills.*' => ['string', 'max:60'],
            'positions' => ['array', 'max:5'], 'positions.*' => ['string', 'max:60'],
            'locations' => ['array', 'max:3'], 'locations.*' => ['string', 'max:60'],
            'hours' => ['nullable', 'in:24,168,720'], 'kind' => ['nullable', 'in:kerja,magang'],
            'sources' => ['array'], 'sources.*' => ['string'],
        ]);

        $skills = $this->clean($data['skills'] ?? [], 25);
        $positions = $this->clean($data['positions'] ?? [], 5) ?: array_slice($skills, 0, 3);
        if (! $positions) {
            return response()->json(['error' => 'Isi minimal satu skill atau posisi.'], 422);
        }
        $available = $this->finder->availableKeys();
        $sources = array_values(array_intersect($data['sources'] ?? ['linkedin'], $available)) ?: array_values(array_intersect(['linkedin'], $available));

        // Situs ini publik: batasi pencarian yang berjalan bersamaan.
        $max = (int) config('platform.jobs.max_running', 3);
        Cache::add('jobsearch:running', 0, 900);
        if (Cache::increment('jobsearch:running') > $max) {
            Cache::decrement('jobsearch:running');

            return response()->json(['error' => 'Server sedang sibuk, coba lagi sebentar lagi.'], 429);
        }

        $params = [
            'positions' => $positions, 'skills' => $skills ?: $positions,
            'locations' => $this->clean($data['locations'] ?? [], 3) ?: ['Indonesia'],
            'hours' => (int) ($data['hours'] ?? 24), 'kind' => ($data['kind'] ?? 'kerja') === 'magang' ? 'magang' : 'kerja',
            'sources' => $sources,
        ];
        $id = Str::random(12);
        Cache::put(RunJobSearch::key($id), [
            'status' => 'running', 'pct' => 0, 'msg' => 'Memulai...', 'results' => [], 'warnings' => [],
            'links' => JobFinder::searchLinks($params['positions'], $params['locations'], $params['kind']),
            'params' => $params,
        ], RunJobSearch::TTL);

        RunJobSearch::dispatchAfterResponse($id, $params);

        return response()->json(['id' => $id]);
    }

    public function status(Request $request, string $id): JsonResponse
    {
        $state = RunJobSearch::state($id);
        if (! $state) {
            return response()->json(['status' => 'error', 'msg' => 'Hasil tidak ditemukan atau sudah kedaluwarsa.']);
        }

        return response()->json(($state['status'] ?? '') === 'done' ? $this->gate($state, $request) : $state);
    }

    /** Semua hasil dikirim apa adanya; `premium` dan `locked` dipertahankan agar bentuk respons tetap sama. */
    private function gate(array $state, Request $request): array
    {
        $state['premium'] = true;
        $state['locked'] = ['count' => 0, 'matches' => []];

        return $state;
    }

    private function clean(array $items, int $limit): array
    {
        $out = [];
        foreach ($items as $x) {
            $x = Str::limit(trim((string) $x), 60, '');
            if ($x !== '' && ! in_array(mb_strtolower($x), array_map('mb_strtolower', $out), true)) {
                $out[] = $x;
            }
        }

        return array_slice($out, 0, $limit);
    }
}
