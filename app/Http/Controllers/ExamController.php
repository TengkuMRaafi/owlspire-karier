<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Account\Preferences;
use App\Services\Billing\EntitlementService;
use App\Models\Career;
use App\Services\Careers\CareerTheme;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use App\Services\Exam\AiParaphrase;
use App\Services\Exam\ExamSession;
use App\Services\Exam\ExamStats;
use App\Services\Exam\ResultInsights;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    public function __construct(
        private ExamSession $session,
        private ExamStats $stats,
        private EntitlementService $entitlements,
    ) {
    }

    /** Jumlah simulasi karier per halaman. */
    public const CAREER_PER_PAGE = 3;

    public function index(Request $request, CareerTheme $themes)
    {
        $types = $this->stats->catalog();
        $careerTypes = $this->stats->careerCatalog();

        // Info karier (ringkasan, skill) digabung ke kartu simulasi; temanya dipakai untuk ikon dan filter.
        $careers = Career::query()
            ->with('skills')
            ->whereIn('exam_type_id', $careerTypes->pluck('id'))
            ->get()
            ->keyBy('exam_type_id');
        $careerThemes = $careers->map(fn (Career $c) => $themes->for($c))->all();
        $themeOptions = collect($careerThemes)->pluck('label', 'key')->unique()->sort()->all();

        $q = trim((string) $request->query('q', ''));
        $bidang = trim((string) $request->query('bidang', ''));
        $terms = preg_split('/\s+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $list = $careerTypes->filter(function (ExamType $t) use ($careerThemes, $careers, $bidang, $terms) {
            $theme = $careerThemes[$t->id] ?? ['key' => 'umum', 'label' => 'Karier'];
            if ($bidang !== '' && $theme['key'] !== $bidang) {
                return false;
            }
            $c = $careers->get($t->id);
            $hay = mb_strtolower($t->name.' '.$t->description.' '.$theme['label'].' '.($c?->division).' '.($c?->summary).' '.($c?->skills->pluck('name')->implode(' ')));
            foreach ($terms as $term) {
                if (! str_contains($hay, $term)) {
                    return false;
                }
            }

            return true;
        })->values();

        // ?all=1: tampilkan semua hasil di halaman yang sama (tanpa pagination).
        $showAll = $request->boolean('all') && $list->count() > self::CAREER_PER_PAGE;
        $perPage = $showAll ? max(1, $list->count()) : self::CAREER_PER_PAGE;

        $lastPage = max(1, (int) ceil($list->count() / $perPage));
        $page = min($lastPage, max(1, (int) $request->query('page', 1)));
        $careerPage = (new LengthAwarePaginator(
            $list->forPage($page, $perPage)->values(),
            $list->count(),
            $perPage,
            $page,
            ['path' => route('exam.index'), 'query' => array_filter(['q' => $q, 'bidang' => $bidang])],
        ))->fragment('karier');

        // Kartu keempat (Simulasi Jalur Karier): baru bisa memilih karier setelah user punya keahlian.
        $mine = $request->user() ? DB::table('user_skills')->where('user_id', $request->user()->id)->pluck('skill_id')->all() : [];
        $careerPick = $careers->map(function (Career $c) use ($careerTypes, $mine) {
            $type = $careerTypes->firstWhere('id', $c->exam_type_id);
            $match = $c->skills->whereIn('id', $mine)->count();

            return $type ? ['name' => $c->name ?: $type->name, 'url' => route('exam.show', $type), 'match' => $match] : null;
        })->filter()->sortBy([['match', 'desc'], ['name', 'asc']])->values();

        $data = [
            'mySkills' => count($mine),
            'careerPick' => $careerPick,
            'types' => $types,
            'careerTypes' => $careerTypes,
            'careerPage' => $careerPage,
            'showAll' => $showAll,
            'careerTotal' => $careerTypes->count(),
            'careerThemes' => $careerThemes,
            'careerInfo' => $careers,
            'themeOptions' => $themeOptions,
            'q' => $q,
            'bidang' => $bidang,
            'tickets' => $this->ticketsFor($request, $types->concat($careerTypes)),
            'running' => $this->runningAttempt($request),
        ];

        // Permintaan dari pencarian langsung (fetch): kirim bagian hasil saja.
        if ($request->header('X-Partial') === 'results') {
            return response(view('exam.partials.career-results', $data)->render())->header('Vary', 'X-Partial');
        }

        return view('exam.index', $data);
    }

    public function show(Request $request, ExamType $type)
    {
        abort_unless($type->is_active, 404);
        $type->load(['segments' => fn ($q) => $q->withCount('questions')]);

        return view('exam.show', [
            'type' => $type,
            'tickets' => $this->ticketsFor($request, collect([$type])),
            'running' => $this->runningAttempt($request),
            'top' => $this->stats->leaderboard($type, 5),
        ]);
    }

    public function start(Request $request, ExamType $type): RedirectResponse
    {
        $data = $request->validate(['mode' => ['required', 'in:trial,full']]);

        // Kalau masih ada percobaan yang sama dan belum habis waktunya, lanjutkan itu (tiket tidak
        $resume = $this->ownedAttempts($request)
            ->where('exam_type_id', $type->id)->where('mode', $data['mode'])
            ->whereNull('finished_at')->where('deadline_at', '>', now())
            ->latest('id')->first();
        if ($resume) {
            return redirect()->route('exam.room', $resume);
        }

        try {
            $attempt = $this->session->start($type, $data['mode'], $request->user(), $this->guestToken($request));
        } catch (\DomainException $e) {
            if ($data['mode'] === 'full' && ! $request->user()) {
                return redirect()->route('login')->with('error', $e->getMessage());
            }

            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('exam.room', $attempt);
    }

    /** Ulangi simulasi: soal yang sama dengan hasil ini, dikerjakan lagi di ruang ujian seperti biasa. */
    public function retake(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);

        // Kalau masih ada simulasi sejenis yang berjalan, lanjutkan itu dulu (tiket tidak terpakai dua kali).
        $running = $this->ownedAttempts($request)
            ->where('exam_type_id', $attempt->exam_type_id)->where('mode', $attempt->mode)
            ->whereNull('finished_at')->where('deadline_at', '>', now())
            ->latest('id')->first();
        if ($running) {
            return redirect()->route('exam.room', $running)->with('status', 'Simulasi ini masih berjalan. Selesaikan dulu sebelum mengulang.');
        }

        try {
            $new = $this->session->retake($attempt, $request->user(), $this->guestToken($request));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('exam.room', $new);
    }

    public function room(Request $request, ExamAttempt $attempt, Preferences $prefs)
    {
        $this->authorizeAttempt($request, $attempt);

        if ($attempt->isFinished()) {
            return redirect()->route('exam.result', $attempt);
        }
        if ($attempt->isOverdue()) {
            $this->session->finish($attempt);

            return redirect()->route('exam.result', $attempt)->with('status', 'Waktu habis. Jawabanmu sudah dinilai otomatis.');
        }

        $attempt->load(['type', 'answers.question.segment']);

        // Kunci jawaban dan pembahasan sengaja TIDAK ikut dikirim ke browser.
        $items = $attempt->answers->map(fn ($a) => [
            'id' => $a->question_id,
            'segment' => $a->question->segment->name,
            'code' => $a->question->segment->code,
            'body' => $a->question->body,
            'image' => $a->question->image_url,
            'options' => $a->question->options,
            'selected' => $a->selected,
            'flagged' => (bool) $a->flagged,
        ])->values();

        return view('exam.room', [
            'attempt' => $attempt,
            'items' => $items,
            'remaining' => $attempt->remainingSeconds(),
            // Tamu memakai nilai bawaan; pengguna masuk memakai preferensi dari halaman Pengaturan.
            'prefs' => $prefs->group($request->user(), 'exam'),
        ]);
    }

    public function answer(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected' => ['nullable', 'string', 'max:5'],
            'flagged' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $this->session->saveAnswer($attempt, (int) $data['question_id'], $data['selected'] ?? null, (bool) ($data['flagged'] ?? false));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json($result + [
            'ok' => true,
            'redirect' => $result['finished'] ? route('exam.result', $attempt) : null,
        ]);
    }

    public function finish(Request $request, ExamAttempt $attempt): RedirectResponse|JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->session->finish($attempt);

        // Ruang ujian memanggil lewat fetch lalu location.replace, supaya Back dari hasil tidak kembali ke soal.
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('exam.result', $attempt)]);
        }

        return redirect()->route('exam.result', $attempt);
    }

    public function result(Request $request, ExamAttempt $attempt, ResultInsights $insights)
    {
        $this->authorizeAttempt($request, $attempt);

        if (! $attempt->isFinished()) {
            if (! $attempt->isOverdue()) {
                return redirect()->route('exam.room', $attempt);
            }
            $this->session->finish($attempt);
        }

        // Baru di sini kunci jawaban dan pembahasan boleh dibuka.
        $attempt->load(['type', 'answers.question.segment']);

        $review = $attempt->answers->map(function ($a) {
            $status = $a->selected === null ? 'kosong' : ($a->is_correct ? 'benar' : 'salah');

            return [
                'no' => 0,
                'qid' => $a->question_id,
                'segment' => $a->question->segment->name,
                'body' => $a->question->body,
                'image' => $a->question->image_url,
                'options' => $a->question->options,
                'selected' => $a->selected,
                'answer' => $a->question->answer,
                'explanation' => $a->question->explanation,
                'status' => $status,
            ];
        })->values()->map(function ($row, $i) {
            $row['no'] = $i + 1;

            return $row;
        });

        return view('exam.result', [
            'attempt' => $attempt,
            'review' => $review,
            'segments' => $attempt->score ?? [],
            'maxTotal' => array_sum(array_column($attempt->score ?? [], 'maks')),
            'insights' => $insights->build($attempt),
            'rank' => $this->stats->rankOf($attempt),
            'isPremium' => $request->user() ? $this->entitlements->isPremium($request->user()->id) : false,
            // Sisa jatah ujian penuh untuk jenis ini (PHP_INT_MAX = Premium/staf), dipakai tombol Ulangi.
            'fullLeft' => $request->user() ? $this->entitlements->fullAttemptsAvailable($request->user()->id, (int) $attempt->exam_type_id) : 0,
            'aiOn' => app(AiParaphrase::class)->enabled(),
        ]);
    }

    /** Ringkasan hasil bergaya Owlspire (parafrase AI). Dimuat belakangan lewat fetch agar halaman hasil tetap cepat. */
    public function aiSummary(Request $request, ExamAttempt $attempt, ResultInsights $insights, AiParaphrase $ai): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        abort_unless($attempt->isFinished(), 404);
        $attempt->load(['type', 'answers.question.segment']);

        $data = $insights->build($attempt);
        $brief = $attempt->mode !== 'full'; // mode coba: ringkas, ujian penuh: lengkap
        $text = $ai->summary($attempt, $data, $brief);

        // Tanpa kunci Groq atau saat panggilan gagal, tetap beri ringkasan berbasis aturan agar blok tidak kosong.
        return response()->json(['text' => $text ?? $ai->fallback($data, $brief), 'ai' => $text !== null]);
    }

    /** Tanya jawab dengan Owlspire tentang hasil satu pengerjaan. Groq bila aktif, kalau tidak jawaban berbasis aturan. */
    public function aiAsk(Request $request, ExamAttempt $attempt, ResultInsights $insights, AiParaphrase $ai): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        abort_unless($attempt->isFinished(), 404);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:300'],
            'history' => ['nullable', 'array', 'max:6'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:1500'],
        ]);

        // Batas tanya per hasil supaya kuota Groq tidak habis oleh satu pengerjaan.
        $key = 'ai:ask:'.$attempt->id;
        $used = (int) Cache::get($key, 0);
        if ($used >= 25) {
            return response()->json(['text' => 'Jatah tanya untuk hasil ini sudah habis. Coba kerjakan simulasi lagi, nanti aku bantu bahas hasil barumu.', 'ai' => false]);
        }
        Cache::put($key, $used + 1, now()->addDay());

        $attempt->load(['type', 'answers.question.segment']);
        $built = $insights->build($attempt);
        $text = $ai->ask($attempt, $built, $data['question'], $data['history'] ?? []);

        return response()->json(['text' => $text ?? $ai->askFallback($built, $data['question']), 'ai' => $text !== null]);
    }

    /** Pembahasan satu soal dengan kata-kata lebih sederhana. Hanya soal yang memang ada di percobaan ini. */
    public function aiExplain(Request $request, ExamAttempt $attempt, AiParaphrase $ai): JsonResponse
    {
        $this->authorizeAttempt($request, $attempt);
        abort_unless($attempt->isFinished(), 404);
        $data = $request->validate(['question_id' => ['required', 'integer']]);
        abort_unless($attempt->answers()->where('question_id', $data['question_id'])->exists(), 404);

        $text = $ai->explain(Question::findOrFail($data['question_id']));

        return $text
            ? response()->json(['text' => $text])
            : response()->json(['message' => 'Penjelasan belum bisa dibuat. Coba lagi sebentar lagi.'], 503);
    }

    public function leaderboard(Request $request)
    {
        $types = $this->stats->catalog();
        $active = $types->concat($this->stats->careerCatalog())->firstWhere('code', $request->query('jenis')) ?? $types->first();

        return view('exam.leaderboard', [
            'types' => $types,
            'active' => $active,
            'rows' => $active ? $this->stats->leaderboard($active, 20) : collect(),
        ]);
    }

    // ---------------------------------------------------------------- helper

    /** Sisa percobaan penuh per jenis ujian (kunci: id jenis). Kosong untuk tamu. */
    private function ticketsFor(Request $request, $types): array
    {
        if (! $request->user()) {
            return [];
        }

        return $types->mapWithKeys(fn ($t) => [$t->id => $this->entitlements->fullAttemptsAvailable($request->user()->id, $t->id)])->all();
    }

    private function ownedAttempts(Request $request)
    {
        $query = ExamAttempt::query();

        return $request->user()
            ? $query->where('user_id', $request->user()->id)
            : $query->whereNull('user_id')->where('guest_token', $this->guestToken($request));
    }

    private function runningAttempt(Request $request): ?ExamAttempt
    {
        return $this->ownedAttempts($request)->whereNull('finished_at')->where('deadline_at', '>', now())
            ->with('type')->latest('id')->first();
    }

    private function guestToken(Request $request): string
    {
        $token = $request->session()->get('exam_guest_token');
        if (! $token) {
            $token = Str::random(40);
            $request->session()->put('exam_guest_token', $token);
        }

        return $token;
    }

    private function authorizeAttempt(Request $request, ExamAttempt $attempt): void
    {
        $owned = $attempt->user_id
            ? ($request->user() && $request->user()->id === (int) $attempt->user_id)
            : ($attempt->guest_token && hash_equals($attempt->guest_token, (string) $request->session()->get('exam_guest_token', '')));

        abort_unless($owned, 404);
    }
}
