<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Services\Careers\ReadinessScore;
use Illuminate\Http\Request;

/** Karier saya: karier target, skor kesiapan, dan katalog untuk memilih target. */
class CareerController extends Controller
{
    public function index(Request $request, ReadinessScore $readiness)
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $divisi = trim((string) $request->query('divisi', ''));
        $target = $user->target_career_id ? Career::with('course')->find($user->target_career_id) : null;

        $careers = Career::where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('summary', 'like', "%{$q}%")))
            ->when($divisi !== '', fn ($query) => $query->where('division', $divisi))
            ->orderBy('rank')->paginate(10)->withQueryString();

        return view('account.career', [
            'target' => $target,
            'readiness' => $target ? $readiness->for($user, $target) : null,
            'careers' => $careers,
            'divisions' => Career::where('is_active', true)->distinct()->orderBy('division')->pluck('division', 'division')->all(),
            'q' => $q,
            'divisi' => $divisi,
        ]);
    }
}
