<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\CvProfile;
use App\Models\InterviewSession;
use App\Services\Ai\AiQuota;
use App\Services\Ai\InterviewCoach;
use App\Services\Billing\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Simulasi wawancara berbasis percakapan: pewawancara mengingat seluruh obrolan, memakai CV dan lowongan,
 * menggali jawaban, dan menutup sendiri. Penilaian dibuat sekali di akhir.
 */
class InterviewController extends Controller
{
    public function __construct(private InterviewCoach $coach, private AiQuota $quota, private EntitlementService $entitlements)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return view('account.interview', [
            'kinds' => InterviewCoach::KINDS,
            'personas' => InterviewCoach::PERSONAS,
            'history' => InterviewSession::where('user_id', $user->id)->latest('id')->limit(8)->get(),
            'running' => InterviewSession::where('user_id', $user->id)->where('status', 'running')->latest('id')->first(),
            'remaining' => $this->quota->remaining($user, 'interview'),
            'limit' => $this->quota->limit($user, 'interview'),
            'premium' => $this->entitlements->isPremium($user->id),
            'aiOn' => $this->coach->enabled(),
            'hasCv' => filled($this->cvText($user->id)),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'role' => ['required', 'string', 'min:3', 'max:120'],
            'kind' => ['required', 'in:'.implode(',', array_keys(InterviewCoach::KINDS))],
            'context' => ['nullable', 'string', 'max:1500'],
        ]);

        if ($running = InterviewSession::where('user_id', $user->id)->where('status', 'running')->latest('id')->first()) {
            return redirect()->route('account.interview.show', $running)->with('status', 'Selesaikan dulu wawancara yang sedang berjalan.');
        }
        if (! $this->coach->enabled()) {
            return back()->withErrors(['role' => 'Penilai AI belum aktif di server ini.'])->withInput();
        }
        if ($this->quota->remaining($user, 'interview') <= 0) {
            return back()->withErrors(['role' => 'Jatah wawancara hari ini sudah habis. Coba lagi besok atau lihat Premium.'])->withInput();
        }

        $role = trim($data['role']);
        $context = trim((string) ($data['context'] ?? ''));
        $opening = $this->coach->avoiding($this->pastQuestions($user->id, $data['kind']))->opening($role, $data['kind'], $this->cvText($user->id), $context);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'role' => $role,
            'kind' => $data['kind'],
            'context' => $context !== '' ? $context : null,
            'questions' => [],
            'answers' => [],
            'transcript' => [['role' => 'interviewer', 'text' => $opening]],
        ]);
        $this->quota->consume($user, 'interview');

        return redirect()->route('account.interview.show', $session);
    }

    /**
     * Pertanyaan pewawancara dari beberapa pertemuan terakhir dengan pewawancara yang sama (terbaru dulu),
     * dipakai supaya pertemuan berikutnya tidak mengulang pertanyaan yang sama.
     *
     * @return array<int,string>
     */
    private function pastQuestions(int $userId, string $kind, ?int $exceptId = null): array
    {
        $out = [];
        $sessions = InterviewSession::where('user_id', $userId)->where('kind', $kind)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->latest('id')->limit(5)->get();

        foreach ($sessions as $s) {
            foreach ($s->turns() as $t) {
                $text = trim((string) ($t['text'] ?? ''));
                if (($t['role'] ?? '') === 'interviewer' && str_contains($text, '?')) {
                    $out[] = $text;
                }
            }
        }

        return array_slice($out, 0, 14);
    }

    public function show(Request $request, InterviewSession $session)
    {
        $this->own($request, $session);
        $session->ensureTranscript(); // sesi lama yang masih berjalan dilanjutkan dengan cara baru

        return view('account.interview-show', [
            'session' => $session,
            'kinds' => InterviewCoach::KINDS,
            'persona' => InterviewCoach::persona($session->kind),
            'greeting' => InterviewCoach::greeting($session->kind, $session->role),
            'lastMood' => $this->lastMood($session),
            'count' => $session->answerCount(),
            'target' => InterviewCoach::TARGET_ANSWERS,
            'canFinish' => $session->answerCount() >= InterviewCoach::MIN_TO_EVALUATE,
        ]);
    }

    public function answer(Request $request, InterviewSession $session): RedirectResponse|JsonResponse
    {
        $this->own($request, $session);
        $session->ensureTranscript();
        $json = $request->expectsJson();
        if ($session->status !== 'running' || $session->isLegacy()) {
            return $json ? response()->json(['message' => 'Wawancara ini sudah selesai.'], 409) : redirect()->route('account.interview.show', $session);
        }

        $data = $request->validate(['answer' => ['required', 'string', 'min:3', 'max:1500']], [
            'answer.min' => 'Jawabanmu terlalu singkat. Tulis beberapa kata atau kalimat.',
        ]);
        $text = trim($data['answer']);

        $turns = $session->turns();
        $turns[] = ['role' => 'candidate', 'text' => $text];

        $reply = $this->coach->avoiding($this->pastQuestions($session->user_id, $session->kind, $session->id))->reply($session->role, $session->kind, $turns, $this->cvText($session->user_id), (string) $session->context);
        if ($reply === null) {
            $msg = 'Pewawancara sedang sibuk. Kirim ulang sebentar lagi; jawabanmu belum terpakai.';

            return $json ? response()->json(['message' => $msg], 503) : back()->withErrors(['answer' => $msg])->withInput();
        }

        $turns[] = ['role' => 'interviewer', 'text' => $reply['text'], 'mood' => $reply['mood']];
        $session->update(['transcript' => $turns, 'status' => $reply['done'] ? 'done' : 'running']);

        if ($json) {
            return response()->json([
                'reply' => $reply['text'],
                'mood' => $reply['mood'],
                'done' => $reply['done'],
                'count' => $session->answerCount(),
                'url' => route('account.interview.show', $session),
            ]);
        }

        return redirect()->route('account.interview.show', $session);
    }

    /** Pengguna mengakhiri wawancara lebih awal; penilaian dibuat di halaman hasil. */
    public function finish(Request $request, InterviewSession $session): RedirectResponse
    {
        $this->own($request, $session);
        $session->ensureTranscript();

        if ($session->status === 'running' && ! $session->isLegacy()) {
            if ($session->answerCount() < InterviewCoach::MIN_TO_EVALUATE) {
                return back()->withErrors(['answer' => 'Jawab minimal '.InterviewCoach::MIN_TO_EVALUATE.' pertanyaan dulu supaya ada yang bisa dinilai.']);
            }
            $turns = $session->turns();
            if (($turns[array_key_last($turns)]['role'] ?? '') === 'interviewer') {
                $turns[] = ['role' => 'interviewer', 'text' => 'Baik, kita sudahi sampai di sini. Terima kasih atas waktunya.'];
            }
            $session->update(['transcript' => $turns, 'status' => 'done']);
        }

        return redirect()->route('account.interview.show', $session);
    }

    /** Membuat penilaian akhir dari seluruh transkrip. Aman dipanggil ulang bila sebelumnya gagal. */
    public function grade(Request $request, InterviewSession $session): RedirectResponse|JsonResponse
    {
        $this->own($request, $session);
        $json = $request->expectsJson();
        $show = route('account.interview.show', $session);

        if ($session->status !== 'done' || $session->isLegacy() || $session->report !== null) {
            return $json ? response()->json(['url' => $show]) : redirect($show);
        }

        $report = $this->coach->evaluate($session->role, $session->kind, $session->pairs(), $this->cvText($session->user_id));
        if ($report === null) {
            $msg = 'Penilai sedang sibuk. Coba lagi sebentar lagi; wawancaramu sudah tersimpan.';

            return $json ? response()->json(['message' => $msg], 503) : redirect($show)->withErrors(['grade' => $msg]);
        }

        $session->update(['report' => $report, 'total_score' => $report['score']]);

        return $json ? response()->json(['url' => $show]) : redirect($show);
    }

    /** Emosi terakhir pewawancara (netral bila belum ada), untuk ekspresi wajah saat halaman dibuka. */
    private function lastMood(InterviewSession $session): string
    {
        foreach (array_reverse($session->turns()) as $t) {
            if (($t['role'] ?? '') === 'interviewer') {
                return in_array($t['mood'] ?? '', InterviewCoach::MOODS, true) ? $t['mood'] : 'netral';
            }
        }

        return 'netral';
    }

    private function cvText(int $userId): string
    {
        return (string) CvProfile::where('user_id', $userId)->latest('id')->value('raw_text');
    }

    private function own(Request $request, InterviewSession $session): void
    {
        abort_unless((int) $session->user_id === (int) $request->user()->id, 404);
    }
}
