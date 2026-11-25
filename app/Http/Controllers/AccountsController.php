<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Services\Billing\EntitlementService;
use App\Models\Career;
use App\Models\CvProfile;
use App\Models\ExamType;
use App\Models\LessonProgress;
use App\Services\Account\PlanAccess;
use App\Services\Careers\ReadinessScore;
use App\Services\Exam\ExamStats;
use Illuminate\Http\Request;

class AccountsController extends Controller
{
    public function index(Request $request, ExamStats $stats, EntitlementService $entitlements, ReadinessScore $readiness, PlanAccess $access)
    {
        $user = $request->user();
        $target = $user->target_career_id ? Career::with('course')->find($user->target_career_id) : null;
        $premium = $entitlements->isPremium($user->id);

        $cvTop = $premium ? collect(CvProfile::where('user_id', $user->id)->first()?->parsed['matches'] ?? [])->first() : null;

        $types = ExamType::where('is_active', true)->orderBy('position')->orderBy('id')->get();

        return view('dashboard.index', [
            // Beli tiket langsung dari dashboard: seleksi resmi dan simulasi karier.
            'ticketOptions' => array_filter([
                'Seleksi resmi' => $types->where('kind', 'selection')->pluck('name', 'id')->all(),
                'Simulasi karier' => $types->where('kind', 'career')->pluck('name', 'id')->all(),
            ]),
            'user' => $user,
            'premium' => $premium,
            'internal' => $user->isInternal(),
            'premiumUntil' => $premium && ! $user->isInternal() ? $entitlements->premiumUntil($user->id) : null,
            'features' => $access->features($premium),
            'topics' => LessonProgress::where('user_id', $user->id)->whereNotNull('completed_at')->count(),
            'cvTop' => $cvTop,
            'cvCareer' => $cvTop ? Career::find($cvTop['career_id']) : null,
            'target' => $target,
            'readiness' => $target ? $readiness->for($user, $target) : null,
            'stats' => $stats->forUser($user->id),
            'tickets' => (int) Entitlement::where('user_id', $user->id)->where('type', 'exam_pass')
                ->where('remaining', '>', 0)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->distinct()->count('reference_id'),
        ]);
    }
}
