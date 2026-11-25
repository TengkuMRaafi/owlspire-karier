<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Services\Billing\EntitlementService;
use Illuminate\Http\Request;

/** Riwayat ujian yang sudah selesai (tujuan tautan "Lihat semua riwayat" di navbar). */
class HistoryController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements)
    {
        $q = trim((string) $request->query('q', ''));
        $mode = in_array($request->query('mode'), ['full', 'trial'], true) ? $request->query('mode') : '';

        $history = ExamAttempt::with('type')->where('user_id', $request->user()->id)->whereNotNull('finished_at')
            ->when($mode !== '', fn ($query) => $query->where('mode', $mode))
            ->when($q !== '', fn ($query) => $query->whereHas('type', fn ($t) => $t->where('name', 'like', "%{$q}%")))
            ->latest('finished_at')->paginate(10)->withQueryString();

        // Sisa jatah ujian penuh per jenis ujian di halaman ini, untuk tombol Ulangi.
        $uid = $request->user()->id;
        $fullLeft = $history->getCollection()->where('mode', 'full')->pluck('exam_type_id')->unique()
            ->mapWithKeys(fn ($id) => [$id => $entitlements->fullAttemptsAvailable($uid, (int) $id)])->all();

        return view('account.history', ['history' => $history, 'q' => $q, 'mode' => $mode, 'fullLeft' => $fullLeft]);
    }
}
