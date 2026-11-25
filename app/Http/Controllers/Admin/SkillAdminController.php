<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Services\Audit\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Kamus skill: dipakai analisis CV dan pencocokan karier. Nama dan alias di sinilah yang dicari di teks CV. */
class SkillAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Skill::query()->orderBy('name');
        if ($q = trim((string) $request->query('cari'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('aliases', 'like', "%{$q}%"));
        }
        if ($cat = $request->query('kategori')) {
            $query->where('category', $cat);
        }
        $skills = $query->paginate(25)->withQueryString();
        $ids = $skills->pluck('id');

        return view('admin.skills.index', [
            'skills' => $skills,
            'categories' => Skill::whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all(),
            'careerCounts' => DB::table('career_skill')->whereIn('skill_id', $ids)->selectRaw('skill_id, count(*) as total')->groupBy('skill_id')->pluck('total', 'skill_id'),
            'userCounts' => DB::table('user_skills')->whereIn('skill_id', $ids)->selectRaw('skill_id, count(*) as total')->groupBy('skill_id')->pluck('total', 'skill_id'),
        ]);
    }

    public function create()
    {
        return view('admin.skills.form', ['skill' => new Skill(), 'categories' => $this->categories()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $skill = Skill::create($data + ['slug' => $this->slug($data['name'])]);
        Audit::log('content.skill_created', $skill);

        return redirect()->route('admin.skills.index')->with('status', "Skill {$skill->name} ditambahkan.");
    }

    public function edit(Skill $skill)
    {
        return view('admin.skills.form', ['skill' => $skill, 'categories' => $this->categories()]);
    }

    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $skill->update($this->validated($request, $skill));
        Audit::log('content.skill_updated', $skill);

        return redirect()->route('admin.skills.index')->with('status', "Skill {$skill->name} diperbarui.");
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $name = $skill->name;
        Audit::log('content.skill_deleted', $skill);
        $skill->delete();

        return redirect()->route('admin.skills.index')->with('status', "Skill {$name} dihapus.");
    }

    private function validated(Request $request, ?Skill $skill = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80', Rule::unique('skills', 'name')->ignore($skill?->id)],
            'category' => ['nullable', 'string', 'max:60'],
            'aliases' => ['nullable', 'string', 'max:2000'],
        ], ['name.unique' => 'Skill dengan nama ini sudah ada.']);

        // Alias: satu per baris atau dipisah koma; dibersihkan, tanpa duplikat, dan tidak sama dengan nama skill.
        $aliases = collect(preg_split('/[\r\n,]+/', (string) ($data['aliases'] ?? '')))
            ->map(fn ($a) => trim($a))->filter(fn ($a) => $a !== '' && mb_strlen($a) <= 60 && mb_strtolower($a) !== mb_strtolower($data['name']))
            ->unique(fn ($a) => mb_strtolower($a))->take(25)->values()->all();

        return ['name' => trim($data['name']), 'category' => filled($data['category'] ?? null) ? trim($data['category']) : null, 'aliases' => $aliases];
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'skill';
        $slug = $base;
        for ($i = 2; Skill::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function categories(): array
    {
        return Skill::whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();
    }
}
