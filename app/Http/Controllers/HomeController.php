<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\SelectionEvent;
use App\Services\Careers\CareerTrend;
use App\Services\Exam\ExamStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __invoke(Request $request, ExamStats $stats, CareerTrend $trend)
    {
        // Landing tetap tampil walau database belum dimigrasi (mis. saat instalasi pertama).
        try {
            $catalog = $stats->catalog();
            $numbers = $stats->publicNumbers();
            $careers = Career::listed()->get();
            $trending = $trend->board();
            // Pilihan peran untuk kartu Simulasi Jalur Karier; keahlian user hanya menyarankan urutan.
            $careerTypes = $stats->careerCatalog();
            $mine = $request->user() ? DB::table('user_skills')->where('user_id', $request->user()->id)->pluck('skill_id')->all() : [];
            $careerPick = Career::query()->with('skills')->whereIn('exam_type_id', $careerTypes->pluck('id'))->get()
                ->map(function (Career $c) use ($careerTypes, $mine) {
                    $type = $careerTypes->firstWhere('id', $c->exam_type_id);

                    return $type ? ['name' => $c->name ?: $type->name, 'url' => route('exam.show', $type), 'match' => $c->skills->whereIn('id', $mine)->count()] : null;
                })->filter()->sortBy([['match', 'desc'], ['name', 'asc']])->values();
            $careerTotal = $careerTypes->count();
            // Top 5 ujian penuh per seleksi untuk bagian Peringkat di landing.
            $boards = $catalog->map(fn ($t) => ['type' => $t, 'rows' => $stats->leaderboard($t, 5)])->values();
            // Tab tambahan: simulasi yang disesuaikan dengan karier pilihan (gabungan semua karier).
            if ($stats->careerCatalog()->isNotEmpty()) {
                $boards->push(['type' => null, 'career' => true, 'rows' => $stats->careerLeaderboard(5)]);
            }
        } catch (\Throwable) {
            $careers = collect();
            $careerPick = collect();
            $careerTotal = 0;
            $boards = collect();
            $catalog = collect();
            $trending = ['items' => collect(), 'live' => false, 'days' => 3, 'updated_at' => now(), 'next_at' => now(), 'totals' => ['jobs' => 0, 'sims' => 0, 'targets' => 0]];
            $numbers = ['pengguna' => 0, 'soal' => 0, 'jenis' => 0, 'selesai' => 0];
        }

        // Jadwal seleksi terdekat untuk strip di landing; kosong bila belum ada atau tabel belum dimigrasi.
        try {
            $selectionEvents = SelectionEvent::upcomingPublished()->limit(3)->get();
        } catch (\Throwable) {
            $selectionEvents = collect();
        }

        $heroFile = collect(['hero.webp', 'hero.jpg', 'hero.png'])
            ->first(fn ($f) => file_exists(public_path('images/'.$f))) ?? 'hero-owlspire.svg';

        return view('home', [
            'catalog' => $catalog,
            'types' => $catalog,
            'careerPick' => $careerPick,
            'careerTotal' => $careerTotal,
            'careers' => $careers,
            'boards' => $boards,
            'trending' => $trending,
            'numbers' => $numbers,
            'selectionEvents' => $selectionEvents,
            'hero' => asset('images/'.$heroFile),
        ]);
    }
}
