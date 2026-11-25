<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\Billing\EntitlementService;
use App\Services\Learning\ProgressService;
use Illuminate\Http\Request;

/** Jalur belajar di dalam akun: kemajuan per jalur dan batas topik Gratis. */
class LearningController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements, ProgressService $progress)
    {
        $uid = $request->user()->id;
        $q = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $courses = Course::where('is_published', true)->with('career')
            ->withCount([
                'lessons as lessons_count' => fn ($l) => $l->where('lessons.is_approved', true),
                'lessons as free_count' => fn ($l) => $l->where('lessons.is_approved', true)->where('lessons.is_free_preview', true),
            ])
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($kategori !== '', fn ($query) => $query->where('category', $kategori))
            ->orderBy('id')->paginate(10)->withQueryString();

        // Target mingguan dari Pengaturan > Belajar; minggu dihitung mulai Senin.
        $goalTarget = (int) $request->user()->pref('learning.weekly_goal');
        $goalDone = $goalTarget > 0
            ? LessonProgress::where('user_id', $uid)->where('completed_at', '>=', now()->startOfWeek())->count()
            : 0;

        return view('account.learning', [
            'goal' => $goalTarget > 0 ? ['target' => $goalTarget, 'done' => $goalDone, 'percent' => min(100, (int) round($goalDone / $goalTarget * 100))] : null,
            'premium' => $entitlements->isPremium($uid),
            'courses' => $courses,
            'progress' => $courses->getCollection()->mapWithKeys(fn ($c) => [$c->id => $progress->percent($uid, $c)])->all(),
            'categories' => Course::where('is_published', true)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all(),
            'q' => $q,
            'kategori' => $kategori,
        ]);
    }
}
