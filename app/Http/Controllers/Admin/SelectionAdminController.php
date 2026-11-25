<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SelectionEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Jadwal seleksi untuk halaman Info seleksi. Diisi manual dari pengumuman resmi. */
class SelectionAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = SelectionEvent::query()->orderByDesc('starts_on')->orderByDesc('id');
        if ($search = trim((string) $request->query('cari'))) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('stage', 'like', "%{$search}%"));
        }
        if (array_key_exists((string) $request->query('kategori'), SelectionEvent::CATEGORIES)) {
            $query->where('category', $request->query('kategori'));
        }
        if (in_array($status = $request->query('status'), ['terbit', 'draf'], true)) {
            $query->where('is_published', $status === 'terbit');
        }

        return view('admin.selections.index', ['events' => $query->paginate(10)->withQueryString()]);
    }

    public function create()
    {
        return view('admin.selections.form', ['event' => new SelectionEvent(['is_published' => true, 'category' => 'cpns'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        SelectionEvent::create($this->validated($request));

        return redirect()->route('admin.selections.index')->with('status', 'Jadwal seleksi ditambahkan.');
    }

    public function edit(SelectionEvent $event)
    {
        return view('admin.selections.form', ['event' => $event]);
    }

    public function update(Request $request, SelectionEvent $event): RedirectResponse
    {
        $event->update($this->validated($request));

        return redirect()->route('admin.selections.index')->with('status', 'Jadwal seleksi diperbarui.');
    }

    public function destroy(SelectionEvent $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.selections.index')->with('status', 'Jadwal seleksi dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(SelectionEvent::CATEGORIES))],
            'stage' => ['nullable', 'string', 'max:60'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'source_url' => ['nullable', 'url:http,https', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
