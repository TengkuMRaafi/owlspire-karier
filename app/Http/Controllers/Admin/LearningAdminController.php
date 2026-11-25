<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\Learning\YouTubeCcClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Meninjau video hasil `belajar:ambil-youtube` dan menambah video secara manual (lisensi wajib diisi). */
class LearningAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Lesson::where('provider', 'youtube')->with('section.course')->latest('id');
        if ($search = trim((string) $request->query('cari'))) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('credit_name', 'like', "%{$search}%"));
        }
        if (in_array($status = $request->query('status'), ['menunggu', 'tayang'], true)) {
            $query->where('is_approved', $status === 'tayang');
        }
        if ($course = $request->query('jalur')) {
            $query->whereHas('section', fn ($q) => $q->where('course_id', $course));
        }

        return view('admin.learning.index', [
            'videos' => $query->paginate(10)->withQueryString(),
            'courses' => Course::orderBy('title')->get(),
            'pending' => Lesson::where('is_approved', false)->where('provider', 'youtube')->count(),
            'hasKey' => app(YouTubeCcClient::class)->configured(),
        ]);
    }

    public function create()
    {
        return view('admin.learning.form', ['lesson' => new Lesson(), 'courses' => Course::orderBy('title')->get(), 'topics' => $this->topics()]);
    }

    public function show(Lesson $lesson)
    {
        $lesson->load('section.course');

        return view('admin.learning.show', ['lesson' => $lesson]);
    }

    public function edit(Lesson $lesson)
    {
        $lesson->load('section');

        return view('admin.learning.form', ['lesson' => $lesson, 'courses' => Course::orderBy('title')->get()]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'credit_name' => ['nullable', 'string', 'max:120'],
            'license' => ['nullable', 'string', 'max:120'], 'goal' => ['nullable', 'string', 'max:600'],
        ]);

        $lesson->update(['title' => $data['title'], 'credit_name' => $data['credit_name'] ?: 'YouTube', 'license' => $data['license'] ?: 'Disematkan lewat pemutar resmi YouTube', 'body' => $data['goal'] ?? null]);

        return redirect()->route('admin.learning.index')->with('status', 'Video diperbarui.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'], 'title' => ['required', 'string', 'max:160'],
            'url' => ['required', 'string', 'max:200'], 'credit_name' => ['nullable', 'string', 'max:120'],
            'license' => ['nullable', 'string', 'max:120'], 'goal' => ['nullable', 'string', 'max:600'],
            'topic_id' => ['nullable', 'integer', 'exists:lessons,id'],
        ]);
        $data['credit_name'] = $data['credit_name'] ?: 'YouTube';
        $data['license'] = $data['license'] ?: 'Disematkan lewat pemutar resmi YouTube';

        $id = YouTubeCcClient::parseId($data['url']);
        if (! $id) {
            return back()->withInput()->withErrors(['url' => 'Tautan atau ID video YouTube tidak dikenali.']);
        }

        // Dipasang ke topik jalur yang sudah ada: topik itu jadi berisi video (judul topik tetap).
        if (! empty($data['topic_id'])) {
            $topic = Lesson::find($data['topic_id']);
            if ($topic && $topic->section?->course_id == $data['course_id']) {
                $topic->update([
                    'type' => 'video', 'provider' => 'youtube', 'video_id' => $id, 'video_title' => $data['title'],
                    'content_url' => 'https://www.youtube.com/watch?v='.$id, 'source_url' => 'https://www.youtube.com/watch?v='.$id,
                    'license' => $data['license'], 'credit_name' => $data['credit_name'], 'is_approved' => true,
                    'body' => $data['goal'] ?? $topic->body,
                ]);

                return redirect()->route('admin.learning.index')->with('status', 'Video dipasang ke topik "'.$topic->title.'".');
            }
        }

        $section = CourseSection::firstOrCreate(['course_id' => $data['course_id'], 'title' => 'Video rekomendasi'], ['position' => 2]);
        Lesson::create([
            'section_id' => $section->id, 'title' => $data['title'], 'slug' => 'yt-'.$id.'-'.Str::lower(Str::random(3)), 'type' => 'video',
            'provider' => 'youtube', 'video_id' => $id, 'content_url' => 'https://www.youtube.com/watch?v='.$id,
            'source_url' => 'https://www.youtube.com/watch?v='.$id, 'license' => $data['license'], 'credit_name' => $data['credit_name'],
            'body' => $data['goal'] ?? null, 'position' => 100 + Lesson::where('section_id', $section->id)->count(), 'is_free_preview' => true, 'is_approved' => true,
        ]);

        return redirect()->route('admin.learning.index')->with('status', 'Video ditambahkan.');
    }

    /** Topik jalur yang masih tanpa video, untuk dropdown "pasang ke topik". @return array<int,array{id:int,course_id:int,label:string}> */
    private function topics(): array
    {
        return Lesson::where(fn ($q) => $q->whereNull('provider')->orWhere('provider', '!=', 'youtube'))
            ->where('slug', 'like', 'topik-%')->with('section')->orderBy('section_id')->orderBy('position')->get()
            ->map(fn ($l) => ['id' => $l->id, 'course_id' => (int) $l->section->course_id, 'label' => $l->title])->all();
    }

    public function approve(Lesson $lesson): RedirectResponse
    {
        $lesson->update(['is_approved' => true]);

        return back()->with('status', 'Video disetujui dan tayang.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return redirect()->route('admin.learning.index')->with('status', 'Video dihapus.');
    }
}
