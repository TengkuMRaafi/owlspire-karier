<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\ExamSegment;
use App\Models\ExamType;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExamSetupController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamType::query()->withCount(['segments', 'attempts'])->orderBy('position')->orderBy('id');
        if ($search = trim((string) $request->query('cari'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }
        if (in_array($kind = $request->query('kategori'), ['selection', 'career'], true)) {
            $query->where('kind', $kind);
        }
        if (in_array($status = $request->query('status'), ['aktif', 'nonaktif'], true)) {
            $query->where('is_active', $status === 'aktif');
        }

        $types = $query->paginate(10)->withQueryString();

        return view('admin.setup.index', [
            'types' => $types,
            // Jumlah soal per jenis ujian dihitung sekali untuk halaman ini.
            'questionCounts' => Question::join('exam_segments', 'questions.segment_id', '=', 'exam_segments.id')
                ->whereIn('exam_segments.exam_type_id', $types->pluck('id'))
                ->selectRaw('exam_segments.exam_type_id as t, count(*) as n')->groupBy('exam_segments.exam_type_id')->pluck('n', 't'),
        ]);
    }

    public function createType()
    {
        return view('admin.setup.form', ['type' => new ExamType(['is_active' => true, 'duration_seconds' => 6000, 'trial_duration_seconds' => 900])]);
    }

    public function storeType(Request $request): RedirectResponse
    {
        $data = $this->typeRules($request, null);
        $type = ExamType::create($data + ['position' => (int) ExamType::max('position') + 1, 'kind' => 'selection']);

        return redirect()->route('admin.setup.show', $type)->with('status', 'Jenis ujian ditambahkan. Sekarang tambahkan segmennya.');
    }

    public function showType(ExamType $examType)
    {
        $examType->load(['segments' => fn ($q) => $q->withCount('questions')]);

        return view('admin.setup.show', ['type' => $examType, 'career' => Career::where('exam_type_id', $examType->id)->first()]);
    }

    public function editType(ExamType $examType)
    {
        return view('admin.setup.form', ['type' => $examType]);
    }

    public function updateType(Request $request, ExamType $examType): RedirectResponse
    {
        $examType->update($this->typeRules($request, $examType));

        return redirect()->route('admin.setup.show', $examType)->with('status', "{$examType->name} diperbarui.");
    }

    public function destroyType(ExamType $examType): RedirectResponse
    {
        if (Career::where('exam_type_id', $examType->id)->exists()) {
            return back()->with('error', 'Jenis ujian ini dipakai sebuah karier. Hapus kariernya di menu Karier.');
        }
        if ($examType->attempts()->exists()) {
            return back()->with('error', 'Jenis ujian ini sudah punya hasil ujian, jadi tidak bisa dihapus. Nonaktifkan saja lewat Ubah.');
        }

        $name = $examType->name;
        $examType->delete(); // segmen dan soalnya ikut terhapus

        return redirect()->route('admin.setup.index')->with('status', "{$name} dihapus.");
    }

    public function createSegment(ExamType $examType)
    {
        return view('admin.setup.segment-form', ['type' => $examType, 'segment' => new ExamSegment(['question_count_trial' => 5, 'question_count_full' => 30])]);
    }

    public function storeSegment(Request $request, ExamType $examType): RedirectResponse
    {
        $data = $this->segmentRules($request);
        if ($examType->segments()->where('code', $data['code'])->exists()) {
            return back()->with('error', 'Kode segmen sudah dipakai di ujian ini.')->withInput();
        }
        $examType->segments()->create($data + ['position' => (int) $examType->segments()->max('position') + 1]);

        return redirect()->route('admin.setup.show', $examType)->with('status', 'Segmen ditambahkan.');
    }

    public function editSegment(ExamSegment $segment)
    {
        return view('admin.setup.segment-form', ['type' => $segment->type, 'segment' => $segment]);
    }

    public function updateSegment(Request $request, ExamSegment $segment): RedirectResponse
    {
        $data = $this->segmentRules($request);
        unset($data['code']); // kode dikunci supaya impor soal tidak putus
        $segment->update($data);

        return redirect()->route('admin.setup.show', $segment->exam_type_id)->with('status', 'Segmen diperbarui.');
    }

    public function destroySegment(ExamSegment $segment): RedirectResponse
    {
        if ($segment->questions()->exists()) {
            return back()->with('error', 'Segmen masih punya soal. Hapus atau pindahkan soalnya dulu.');
        }
        $segment->delete();

        return back()->with('status', 'Segmen dihapus.');
    }

    private function typeRules(Request $request, ?ExamType $current): array
    {
        $data = $request->validate([
            'code' => [$current ? 'sometimes' : 'required', 'alpha_dash', 'max:30', 'unique:exam_types,code'.($current ? ','.$current->id : '')],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'trial_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'min_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pass_mode' => ['nullable', 'in:total,segment'],
            'seg_min' => ['nullable', 'array'],
            'seg_min.*' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $rules = ['min_percent' => (float) ($data['min_percent'] ?? 60)];
        if (($data['pass_mode'] ?? 'total') === 'segment' && $current) {
            // Nilai minimal diisi dalam poin; disimpan sebagai persen dari poin maksimal segmen.
            $pts = (int) config('platform.exam.points_per_correct', 5);
            $segRules = [];
            foreach ($current->segments as $seg) {
                $raw = $data['seg_min'][$seg->code] ?? null;
                $maks = (int) $seg->question_count_full * $pts;
                if ($raw === null || $raw === '' || $maks <= 0) {
                    continue;
                }
                $segRules[$seg->code] = round(min(100, (float) $raw / $maks * 100), 4);
            }
            if ($segRules === []) {
                back()->withInput()->withErrors(['seg_min' => 'Isi nilai minimal untuk setidaknya satu segmen, atau pilih "Total skor".'])->throwResponse();
            }
            $rules['segments'] = $segRules;
        }

        $out = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_seconds' => $data['duration_minutes'] * 60,
            'trial_duration_seconds' => $data['trial_minutes'] * 60,
            'passing_rules' => $rules,
            'is_active' => $request->boolean('is_active'),
        ];
        if (isset($data['position'])) {
            $out['position'] = $data['position'];
        }
        if (! $current) {
            $out['code'] = strtolower($data['code']);
        }

        return $out;
    }

    private function segmentRules(Request $request): array
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
            'question_count_trial' => ['required', 'integer', 'min:0', 'max:500'],
            'question_count_full' => ['required', 'integer', 'min:0', 'max:500'],
        ]);
        $data['code'] = strtolower($data['code']);

        return $data;
    }
}
