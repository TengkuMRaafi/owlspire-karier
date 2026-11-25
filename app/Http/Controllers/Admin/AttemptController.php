<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamAttempt::with(['user', 'type'])->whereNotNull('finished_at');

        if ($type = $request->query('jenis')) {
            $query->where('exam_type_id', $type);
        }
        if (in_array($mode = $request->query('mode'), ['trial', 'full'], true)) {
            $query->where('mode', $mode);
        }
        if ($search = trim((string) $request->query('cari'))) {
            $query->where(fn ($q) => $q
                ->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('type', fn ($t) => $t->where('name', 'like', "%{$search}%")));
        }

        return view('admin.attempts.index', [
            'attempts' => $query->latest('finished_at')->paginate(10)->withQueryString(),
            'types' => ExamType::orderBy('position')->get(),
        ]);
    }

    public function show(ExamAttempt $attempt)
    {
        $attempt->load(['user', 'type']);

        return view('admin.attempts.show', [
            'attempt' => $attempt,
            'answered' => $attempt->answers()->whereNotNull('selected')->count(),
            'correct' => $attempt->answers()->where('is_correct', true)->count(),
            'total' => $attempt->answers()->count(),
        ]);
    }

    public function destroy(ExamAttempt $attempt): RedirectResponse
    {
        $attempt->delete(); // jawaban ikut terhapus

        return redirect()->route('admin.attempts.index')->with('status', 'Hasil ujian dihapus.');
    }
}
