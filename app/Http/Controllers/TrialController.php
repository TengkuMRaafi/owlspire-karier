<?php

namespace App\Http\Controllers;

use App\Services\Careers\CareerDirection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Coba simulasi umum: soal pengetahuan dasar, 5 menit. Gratis untuk semua pengguna, lengkap dengan skor dan pembahasan. */
class TrialController extends Controller
{
    public function show(Request $request, CareerDirection $direction)
    {
        return view('exam.trial', [
            'questions' => $direction->trialQuestions(),
            'minutes' => (int) config('arah-karier.trial_minutes', 5),
            'loggedIn' => (bool) $request->user(),
        ]);
    }

    /** Jawaban kosong (waktu habis) diperbolehkan. Kunci jawaban baru dikirim setelah jawaban dikumpulkan. */
    public function submit(Request $request, CareerDirection $direction): JsonResponse
    {
        $n = $direction->trialCount();
        $data = $request->validate([
            'answers' => ['required', 'array', "size:{$n}"],
            'answers.*' => ['nullable', 'integer', 'between:0,3'],
        ]);
        $answers = array_map(fn ($a) => $a === null ? null : (int) $a, array_values($data['answers']));

        return response()->json($direction->grade($answers));
    }
}
