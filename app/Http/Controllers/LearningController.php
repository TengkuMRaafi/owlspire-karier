<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Billing\EntitlementService;
use App\Services\Careers\CareerTheme;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Services\Learning\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LearningController extends Controller
{
    public function __construct(private EntitlementService $entitlements, private ProgressService $progress)
    {
    }

    /** Jumlah jalur per halaman di daftar Belajar. */
    public const PER_PAGE = 3;

    public function index(Request $request, CareerTheme $themes)
    {
        $all = Course::where('is_published', true)
            ->with('career')
            ->withCount([
                'sections',
                'lessons as lessons_count' => fn ($q) => $q->where('lessons.is_approved', true),
            ])
            ->orderBy('id')->get();
        $uid = $request->user()?->id;
        $progress = $uid ? $all->mapWithKeys(fn ($c) => [$c->id => $this->progress->percent($uid, $c)])->all() : [];

        $q = trim((string) $request->query('q', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $categories = $all->pluck('category')->filter()->unique()->sort()->values();

        $terms = preg_split('/\s+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $list = $all->filter(function (Course $c) use ($kategori, $terms) {
            if ($kategori !== '' && $c->category !== $kategori) {
                return false;
            }
            $hay = mb_strtolower(implode(' ', [$c->career?->name ?? $c->title, $c->career?->summary ?? $c->summary, $c->category, $c->career?->division]));
            foreach ($terms as $t) {
                if (! str_contains($hay, $t)) {
                    return false;
                }
            }

            return true;
        })->values();

        // Dipaginasi dari koleksi, sama seperti katalog Karier.
        $lastPage = max(1, (int) ceil($list->count() / self::PER_PAGE));
        $page = min($lastPage, max(1, (int) $request->query('page', 1)));
        $courses = (new LengthAwarePaginator(
            $list->forPage($page, self::PER_PAGE)->values(),
            $list->count(),
            self::PER_PAGE,
            $page,
            ['path' => route('learning.index'), 'query' => array_filter(['q' => $q, 'kategori' => $kategori])],
        ))->fragment('jalur');

        $data = [
            'courses' => $courses,
            'total' => $all->count(),
            'categories' => $categories,
            'q' => $q,
            'kategori' => $kategori,
            'themes' => $themes,
            'progress' => $progress,
            'continue' => $all
                ->filter(fn ($c) => ($progress[$c->id] ?? 0) > 0 && ($progress[$c->id] ?? 0) < 100)
                ->sortByDesc(fn ($c) => $progress[$c->id])->first(),
            'sources' => config('platform.learning.sources', []),
        ];

        // Permintaan dari pencarian langsung (fetch): kirim bagian hasil saja.
        if ($request->header('X-Partial') === 'results') {
            return response(view('learning.partials.results', $data)->render())->header('Vary', 'X-Partial');
        }

        return view('learning.index', $data);
    }

    public function show(Request $request, Course $course)
    {
        abort_unless($course->is_published, 404);
        $lessons = $this->visibleLessons($course);
        $uid = $request->user()?->id;

        return view('learning.show', [
            'course' => $course->load(['career', 'skills']),
            'sections' => $course->sections()->orderBy('position')->get()->each(fn ($s) => $s->setRelation('lessons', $lessons->where('section_id', $s->id)->values())),
            'done' => $uid ? LessonProgress::where('user_id', $uid)->whereNotNull('completed_at')->pluck('lesson_id')->all() : [],
            'premium' => $this->entitlements->isPremium($uid),
            'percent' => $uid ? $this->progress->percent($uid, $course) : 0,
        ]);
    }

    public function lesson(Request $request, Course $course, int $lesson)
    {
        abort_unless($course->is_published, 404);
        $lesson = $this->visibleLessons($course)->firstWhere('id', $lesson);
        abort_unless($lesson, 404);

        $uid = $request->user()?->id;
        $premium = $this->entitlements->isPremium($uid);
        $locked = ! $lesson->is_free_preview && ! $premium;

        $all = $this->visibleLessons($course);
        $i = $all->search(fn ($l) => $l->id === $lesson->id);

        // Soal simulasi karier yang disusun dari topik ini (topik soal = "Materi: <judul topik>").
        $course->loadMissing('career');
        $questionCount = $course->career
            ? Question::where('topic', 'Materi: '.$lesson->title)
                ->whereHas('segment.type', fn ($q) => $q->where('code', 'karier-'.$course->career->slug))
                ->count()
            : 0;

        return view('learning.lesson', [
            'course' => $course, 'lesson' => $lesson, 'locked' => $locked,
            'pos' => $i + 1, 'total' => $all->count(),
            'prev' => $i > 0 ? $all[$i - 1] : null, 'next' => $all[$i + 1] ?? null,
            'completed' => $uid ? LessonProgress::where('user_id', $uid)->where('lesson_id', $lesson->id)->whereNotNull('completed_at')->exists() : false,
            'questionCount' => $questionCount,
            'sources' => config('platform.learning.sources', []),
        ]);
    }

    public function complete(Request $request, Course $course, int $lesson): RedirectResponse
    {
        $lesson = $this->visibleLessons($course)->firstWhere('id', $lesson);
        abort_unless($lesson, 404);

        if (! $lesson->is_free_preview && ! $this->entitlements->isPremium($request->user()->id)) {
            return back()->with('error', 'Topik ini khusus Premium.');
        }

        LessonProgress::updateOrCreate(['user_id' => $request->user()->id, 'lesson_id' => $lesson->id], ['completed_at' => now()]);

        return back()->with('status', 'Topik ditandai selesai.');
    }

    /** Hanya materi yang sudah disetujui admin. */
    private function visibleLessons(Course $course)
    {
        return Lesson::whereIn('section_id', $course->sections()->pluck('id'))->where('is_approved', true)
            ->orderBy('position')->orderBy('id')->get();
    }
}
