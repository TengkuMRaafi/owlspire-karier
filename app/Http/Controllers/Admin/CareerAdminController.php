<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\ExamSegment;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Daftar karier dinamis: tambah, ubah urutan, aktif/nonaktif. */
class CareerAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Career::query()->with('examType')->orderBy('rank')->orderBy('id');
        if ($search = trim((string) $request->query('cari'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('division', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%"));
        }
        if ($division = trim((string) $request->query('divisi'))) {
            $query->where('division', $division);
        }
        if (in_array($status = $request->query('status'), ['aktif', 'nonaktif'], true)) {
            $query->where('is_active', $status === 'aktif');
        }

        $careers = $query->paginate(10)->withQueryString();

        return view('admin.careers.index', [
            'careers' => $careers,
            'divisions' => Career::query()->distinct()->orderBy('division')->pluck('division', 'division')->all(),
            // Jumlah soal per simulasi dihitung sekali untuk halaman ini, bukan per baris.
            'questionCounts' => Question::join('exam_segments', 'questions.segment_id', '=', 'exam_segments.id')
                ->whereIn('exam_segments.exam_type_id', $careers->pluck('exam_type_id')->filter())
                ->selectRaw('exam_segments.exam_type_id as t, count(*) as n')->groupBy('exam_segments.exam_type_id')->pluck('n', 't'),
        ]);
    }

    /** Halaman tambah karier (terpisah dari daftar). */
    public function create()
    {
        return view('admin.careers.form', [
            'career' => new Career(['is_active' => true]),
            'divisions' => Career::query()->distinct()->orderBy('division')->pluck('division'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'division' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:400'],
        ]);

        $slug = Str::slug($data['name']);
        $slug = Career::where('slug', $slug)->exists() ? $slug.'-'.Str::lower(Str::random(3)) : $slug;
        $cfg = config('platform.career_exam');

        $type = ExamType::create([
            'code' => 'karier-'.$slug, 'name' => 'Simulasi '.$data['name'], 'description' => $data['summary'], 'kind' => 'career',
            'duration_seconds' => $cfg['duration_seconds'], 'trial_duration_seconds' => $cfg['trial_duration_seconds'],
            'passing_rules' => ['min_percent' => $cfg['min_percent']], 'is_active' => true, 'position' => 100 + (int) Career::max('rank') + 1,
        ]);
        foreach ([['konsep', 'Konsep Dasar'], ['kasus', 'Studi Kasus'], ['praktik', 'Alat & Praktik']] as $i => [$code, $name]) {
            ExamSegment::create(['exam_type_id' => $type->id, 'code' => $code, 'name' => $name, 'question_count_trial' => $cfg['trial_per_segment'], 'question_count_full' => \App\Services\Exam\FullExamPlan::split((int) $cfg['full_total'], 3)[$i], 'position' => $i + 1]);
        }

        Career::create($data + ['slug' => $slug, 'rank' => (int) Career::max('rank') + 1, 'is_active' => true, 'exam_type_id' => $type->id,
            'details' => ['does' => [], 'tags' => [], 'path' => [], 'tren' => null]]);

        return redirect()->route('admin.careers.index')->with('status', 'Karier ditambahkan. Tambahkan soalnya di Bank soal (pilih simulasi baru ini).');
    }

    public function show(Career $career)
    {
        $career->load('examType.segments');
        $questions = $career->examType
            ? Question::whereIn('segment_id', $career->examType->segments->pluck('id'))->count()
            : 0;

        return view('admin.careers.show', ['career' => $career, 'questions' => $questions, 'theme' => app(\App\Services\Careers\CareerTheme::class)->for($career)]);
    }

    public function edit(Career $career)
    {
        return view('admin.careers.form', [
            'career' => $career,
            'divisions' => Career::query()->distinct()->orderBy('division')->pluck('division'),
        ]);
    }

    public function update(Request $request, Career $career): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'division' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:400'], 'rank' => ['required', 'integer', 'min:1', 'max:999'],
            'tren' => ['nullable', 'string', 'max:200'], 'tags' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $details = $career->details ?? [];
        $details['tren'] = $data['tren'] ?? null ?: null;
        $details['tags'] = collect(explode(',', (string) ($data['tags'] ?? '')))->map(fn ($t) => Str::lower(trim($t)))->filter()->values()->all();

        $career->update([
            'name' => $data['name'], 'division' => $data['division'], 'summary' => $data['summary'],
            'rank' => $data['rank'], 'is_active' => $request->boolean('is_active'), 'details' => $details,
        ]);
        $career->examType?->update(['is_active' => $career->is_active]);

        return redirect()->route('admin.careers.index')->with('status', 'Karier diperbarui.');
    }

    /** Hapus karier beserta simulasinya (segmen, soal, dan percobaan ikut terhapus). */
    public function destroy(Career $career): RedirectResponse
    {
        $name = $career->name;

        User::where('target_career_id', $career->id)->update(['target_career_id' => null]);
        Course::where('career_id', $career->id)->update(['career_id' => null]);
        $career->examType?->delete();
        $career->delete();

        return redirect()->route('admin.careers.index')->with('status', "Karier {$name} dihapus.");
    }
}
