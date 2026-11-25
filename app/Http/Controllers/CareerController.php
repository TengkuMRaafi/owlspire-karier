<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Billing\EntitlementService;
use App\Models\Career;
use App\Services\Careers\CareerDetail;
use App\Services\Careers\CareerDirection;
use App\Services\Careers\CareerTrend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    public function show(Request $request, Career $career, EntitlementService $entitlements, CareerTrend $trend, CareerDetail $detail)
    {
        abort_unless($career->is_active, 404);
        $career->load(['skills', 'course.sections.lessons', 'examType.segments' => fn ($q) => $q->withCount('questions')]);

        $type = $career->examType;
        $user = $request->user();
        $isTarget = $user && (int) $user->target_career_id === $career->id;
        $row = $trend->board()['items']->first(fn ($r) => $r['career']->id === $career->id);

        return view('careers.show', [
            'career' => $career,
            'type' => $type,
            'tickets' => $type && $user ? [$type->id => $entitlements->fullAttemptsAvailable($user->id, $type->id)] : [],
            'isTarget' => $isTarget,
            'd' => $detail->for($career, $user, $row, $isTarget),
        ]);
    }

    public function target(Request $request, Career $career): RedirectResponse
    {
        $request->user()->forceFill(['target_career_id' => $career->id])->save();

        return back()->with('status', "{$career->name} dijadikan karier targetmu. Skor kesiapan muncul di dashboard.");
    }

    /** Tes arah karier dengan soal situasi (tampilan karier saja; coba simulasi ada di halaman sendiri). */
    public function quiz(Request $request, CareerDirection $direction)
    {
        return view('careers.quiz', [
            'questions' => $direction->questions(),
            'loggedIn' => (bool) $request->user(),
        ]);
    }

    /** Hasil tes: lengkap untuk semua pengguna, dengan alasan, profil, dan langkah berikutnya. */
    public function analyze(Request $request, CareerDirection $direction): JsonResponse
    {
        $n = $direction->questionCount();
        $data = $request->validate([
            'answers' => ['required', 'array', "size:{$n}"],
            'answers.*' => ['required', 'integer', 'between:0,3'],
        ]);

        return response()->json($direction->analyze(array_map('intval', array_values($data['answers']))));
    }
}
