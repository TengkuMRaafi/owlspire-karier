<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSegment;
use App\Models\ExamType;
use App\Models\Question;
use App\Services\Exam\QuestionImporter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    private const IMAGE_DIR = 'uploads/questions';

    public function index(Request $request)
    {
        $query = Question::query()->with('segment.type');

        if ($type = $request->query('jenis')) {
            $query->whereHas('segment.type', fn ($q) => $q->where('code', $type));
        }
        if ($segment = $request->query('segmen')) {
            $query->where('segment_id', $segment);
        }
        if ($request->query('trial') !== null && $request->query('trial') !== '') {
            $query->where('is_trial', (bool) $request->query('trial'));
        }
        if ($search = trim((string) $request->query('cari'))) {
            $query->where('body', 'like', '%'.$search.'%');
        }

        return view('admin.questions.index', [
            'questions' => $query->latest('id')->paginate(10)->withQueryString(),
            'types' => ExamType::with('segments')->orderBy('position')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.questions.form', [
            'question' => new Question(['options' => ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''], 'answer' => 'A']),
            'types' => ExamType::with('segments')->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Question::create($data + $this->saveImage($request, null) + ['uid' => (string) Str::uuid()]);

        return redirect()->route('admin.questions.index')->with('status', 'Soal ditambahkan.');
    }

    public function show(Question $question)
    {
        $question->load('segment.type');

        return view('admin.questions.show', ['question' => $question]);
    }

    public function edit(Question $question)
    {
        $options = $question->options ?? [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $k) {
            $options[$k] ??= '';
        }
        $question->options = $options;

        return view('admin.questions.form', [
            'question' => $question,
            'types' => ExamType::with('segments')->orderBy('position')->get(),
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $data = $this->validated($request);
        $question->update($data + $this->saveImage($request, $question));

        return redirect()->route('admin.questions.index')->with('status', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->deleteImage($question->image);
        $question->delete();

        return back()->with('status', 'Soal dihapus.');
    }

    public function importForm(Request $request)
    {
        $segments = ExamSegment::query()->with('type')->withCount('questions')->orderBy('exam_type_id')->orderBy('position')->get();

        $rows = $segments->map(fn (ExamSegment $s) => [
            'type' => $s->type?->name ?? '-',
            'code' => $s->type?->code ?? '-',
            'segment' => $s->name,
            'total' => (int) $s->questions_count,
            'trial' => (int) Question::where('segment_id', $s->id)->where('is_trial', true)->count(),
            'full' => (int) $s->question_count_full,
        ]);

        // Tabel stok dibatasi 10 baris per halaman; halaman lain lewat pagination (?stok=2)
        $perPage = 10;
        $page = max(1, (int) $request->query('stok', 1));
        $page = min($page, max(1, (int) ceil($rows->count() / $perPage)));
        $stock = (new LengthAwarePaginator($rows->forPage($page, $perPage)->values()->all(), $rows->count(), $perPage, $page, [
            'path' => $request->url(),
            'pageName' => 'stok',
        ]))->withQueryString()->fragment('stok');

        return view('admin.questions.import', ['stock' => $stock]);
    }

    public function import(Request $request, QuestionImporter $importer): RedirectResponse
    {
        $request->validate([
            'files' => ['nullable', 'array', 'max:25'],
            'files.*' => ['file', 'max:10240', 'extensions:json,csv,txt'],
            'file' => ['nullable', 'file', 'max:10240', 'extensions:json,csv,txt'],
        ], [
            'files.*.max' => 'Ukuran tiap berkas maksimal 10 MB.',
            'files.*.extensions' => 'Format berkas harus .json atau .csv.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
            'file.extensions' => 'Format berkas harus .json atau .csv.',
        ]);

        $uploads = array_values(array_filter(array_merge((array) $request->file('files', []), [$request->file('file')])));
        if ($uploads === []) {
            return back()->with('error', 'Pilih berkas .json atau .csv dulu.');
        }

        $total = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $errors = [];

        foreach ($uploads as $file) {
            $name = $file->getClientOriginalName();
            $content = (string) file_get_contents($file->getRealPath());

            try {
                $rows = strtolower($file->getClientOriginalExtension()) === 'csv'
                    ? $importer->parseCsv($content)
                    : $importer->parseJson($content);
            } catch (\InvalidArgumentException $e) {
                $errors[] = "{$name}: {$e->getMessage()}";

                continue;
            }

            if ($rows === []) {
                $errors[] = "{$name}: tidak ada soal yang terbaca.";

                continue;
            }

            $r = $importer->import($rows);
            foreach (['created', 'updated', 'skipped'] as $k) {
                $total[$k] += $r[$k];
            }
            foreach (array_slice($r['errors'], 0, 5) as $err) {
                $errors[] = "{$name} - {$err}";
            }
        }

        $message = "Impor selesai ({$total['created']} soal baru, {$total['updated']} diperbarui, {$total['skipped']} dilewati) dari ".count($uploads).' berkas.';

        return back()->with($total['created'] + $total['updated'] > 0 ? 'status' : 'error', $message)
            ->with('import_errors', array_slice($errors, 0, 40));
    }

    public function template(string $format)
    {
        if ($format === 'csv') {
            $csv = "exam_type,segment,question,option_a,option_b,option_c,option_d,option_e,answer,explanation,is_trial\n"
                .'cpns,twk,"Sila ke-3 Pancasila berbunyi...","Ketuhanan Yang Maha Esa","Kemanusiaan yang adil dan beradab","Persatuan Indonesia","Kerakyatan yang dipimpin oleh hikmat kebijaksanaan","Keadilan sosial bagi seluruh rakyat Indonesia",C,"Sila ketiga adalah Persatuan Indonesia.",1'."\n";

            return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="templat-soal.csv"']);
        }

        $json = json_encode([[
            'examType' => 'cpns', 'segmentId' => 'twk',
            'q' => 'Sila ke-3 Pancasila berbunyi...',
            'opts' => ['A. Ketuhanan Yang Maha Esa', 'B. Kemanusiaan yang adil dan beradab', 'C. Persatuan Indonesia', 'D. Kerakyatan yang dipimpin oleh hikmat kebijaksanaan', 'E. Keadilan sosial bagi seluruh rakyat Indonesia'],
            'ans' => 'C', 'exp' => 'Sila ketiga adalah Persatuan Indonesia.', 'isTrial' => true,
        ]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($json, 200, ['Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="templat-soal.json"']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'segment_id' => ['required', 'integer', 'exists:exam_segments,id'],
            'body' => ['required', 'string', 'max:5000'],
            'options' => ['required', 'array'],
            'options.*' => ['nullable', 'string', 'max:1000'],
            'answer' => ['required', 'in:A,B,C,D,E'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'is_trial' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ], [
            'image.image' => 'Berkas harus berupa gambar (JPG, PNG, WEBP, atau GIF).',
            'image.mimes' => 'Format gambar harus JPG, PNG, WEBP, atau GIF.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
            'image.uploaded' => 'Gambar gagal diunggah. Pastikan ukurannya tidak lebih dari 2 MB.',
            'segment_id.required' => 'Pilih segmen.',
            'body.required' => 'Teks soal wajib diisi.',
            'answer.required' => 'Pilih kunci jawaban.',
        ]);

        $options = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $k) {
            $text = trim((string) ($data['options'][$k] ?? ''));
            if ($text !== '') {
                $options[$k] = $text;
            }
        }
        if (count($options) < 2) {
            back()->withInput()->withErrors(['options' => 'Isi minimal 2 pilihan jawaban.'])->throwResponse();
        }
        if (! isset($options[$data['answer']])) {
            back()->withInput()->withErrors(['answer' => 'Kunci jawaban harus salah satu pilihan yang terisi.'])->throwResponse();
        }

        return [
            'segment_id' => $data['segment_id'],
            'body' => trim($data['body']),
            'options' => $options,
            'answer' => $data['answer'],
            'explanation' => trim((string) ($data['explanation'] ?? '')) ?: null,
            'is_trial' => $request->boolean('is_trial'),
        ];
    }

    /** Simpan/ganti/hapus gambar soal. Mengembalikan kolom yang perlu ikut disimpan (kosong bila tidak berubah). */
    private function saveImage(Request $request, ?Question $question): array
    {
        $old = $question?->image;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = Str::random(32).'.'.$file->extension();
            $file->move(public_path(self::IMAGE_DIR), $name);
            $this->deleteImage($old);

            return ['image' => $name];
        }

        if ($old && $request->boolean('remove_image')) {
            $this->deleteImage($old);

            return ['image' => null];
        }

        return [];
    }

    private function deleteImage(?string $name): void
    {
        if ($name) {
            File::delete(public_path(self::IMAGE_DIR.'/'.basename($name)));
        }
    }
}
