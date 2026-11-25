<?php

namespace App\Http\Controllers;

use App\Models\ExamType;
use App\Models\SelectionEvent;
use Illuminate\Http\Request;

/** Info seleksi dan hitung mundur: halaman publik dan versi di dalam akun memakai data yang sama. */
class SelectionController extends Controller
{
    public function index(Request $request)
    {
        return view('selection.index', $this->data($request));
    }

    public function account(Request $request)
    {
        return view('account.selections', $this->data($request));
    }

    private function data(Request $request): array
    {
        $category = (string) $request->query('kategori', '');
        $category = array_key_exists($category, SelectionEvent::CATEGORIES) ? $category : '';

        $base = fn () => SelectionEvent::published()->when($category !== '', fn ($q) => $q->where('category', $category));

        return [
            'category' => $category,
            'upcoming' => $base()->upcoming()->orderBy('starts_on')->orderBy('id')->get(),
            'past' => $base()->past()->orderByDesc('starts_on')->limit(6)->get(),
            // Tombol "Latihan" hanya muncul untuk kategori yang punya simulasi aktif.
            'examCodes' => ExamType::selection()->where('is_active', true)->pluck('code')->all(),
            'portals' => (array) config('platform.selection.portals', []),
        ];
    }
}
