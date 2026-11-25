<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use App\Services\Billing\EntitlementService;
use App\Services\Exam\ExamStats;
use Illuminate\Http\Request;

/** Simulasi di dalam akun: daftar seleksi resmi dan karier dalam satu tabel. Riwayat ada di HistoryController. */
class ExamsController extends Controller
{
    public function index(Request $request, ExamStats $stats, EntitlementService $entitlements)
    {
        $uid = $request->user()->id;
        $q = trim((string) $request->query('q', ''));
        $jenis = in_array($request->query('jenis'), ['selection', 'career'], true) ? $request->query('jenis') : '';

        $types = ExamType::where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->when($jenis !== '', fn ($query) => $query->where('kind', $jenis))
            ->with(['segments' => fn ($s) => $s->withCount('questions')])
            ->orderByRaw("case kind when 'selection' then 0 else 1 end")->orderBy('position')->orderBy('id')
            ->paginate(10)->withQueryString();

        return view('account.exams', [
            'premium' => $entitlements->isPremium($uid),
            'types' => $types,
            'tickets' => $types->getCollection()->mapWithKeys(fn ($t) => [$t->id => $entitlements->fullAttemptsAvailable($uid, $t->id)])->all(),
            'running' => $stats->forUser($uid)['berjalan'],
            'q' => $q,
            'jenis' => $jenis,
        ]);
    }
}
