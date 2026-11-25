<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Satu tahap jadwal seleksi (pendaftaran, ujian, pengumuman) yang diisi admin dari sumber resmi. */
class SelectionEvent extends Model
{
    /** Kode kategori => label. Kode cpns, bumn, dan polri sama dengan kode jenis ujian simulasi. */
    public const CATEGORIES = [
        'cpns' => 'CPNS',
        'bumn' => 'BUMN',
        'polri' => 'Polri',
        'tni' => 'TNI',
        'kedinasan' => 'Kedinasan',
        'lainnya' => 'Lainnya',
    ];

    protected $table = 'selection_events';

    protected $guarded = [];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_published' => 'boolean',
    ];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    /** Belum lewat: berakhir hari ini atau nanti (tanpa tanggal akhir, dihitung dari tanggal mulai). */
    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->whereRaw('COALESCE(ends_on, starts_on) >= ?', [Carbon::today()->toDateString()]);
    }

    public function scopePast(Builder $q): Builder
    {
        return $q->whereRaw('COALESCE(ends_on, starts_on) < ?', [Carbon::today()->toDateString()]);
    }

    /** Yang tampil di landing: terbit dan belum lewat, paling dekat dulu. */
    public function scopeUpcomingPublished(Builder $q): Builder
    {
        return $q->published()->upcoming()->orderBy('starts_on')->orderBy('id');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    /**
     * Posisi acara terhadap hari ini.
     *
     * @return array{state:string, days:int, target:Carbon, label:string}
     *   state: soon (belum mulai) | live (berlangsung) | done (selesai); target = waktu yang dihitung mundur.
     */
    public function window(): array
    {
        $today = Carbon::today();
        $start = $this->starts_on->copy()->startOfDay();
        $end = ($this->ends_on ?? $this->starts_on)->copy()->endOfDay();

        if ($today->lt($start)) {
            $days = (int) abs($today->diffInDays($start));

            return ['state' => 'soon', 'days' => $days, 'target' => $start, 'label' => $days === 1 ? 'Besok' : 'H-'.$days];
        }

        if (now()->lte($end)) {
            $days = (int) abs($today->diffInDays($end->copy()->startOfDay()));

            return ['state' => 'live', 'days' => $days, 'target' => $end, 'label' => $days === 0 ? 'Berakhir hari ini' : 'Sisa '.$days.' hari'];
        }

        return ['state' => 'done', 'days' => (int) abs($end->copy()->startOfDay()->diffInDays($today)), 'target' => $end, 'label' => 'Selesai'];
    }

    /** Rentang tanggal untuk ditampilkan, mis. "3 Nov 2026" atau "3 - 20 Nov 2026". */
    public function rangeLabel(): string
    {
        $start = $this->starts_on;
        $end = $this->ends_on;

        if (! $end || $end->isSameDay($start)) {
            return $start->translatedFormat('d M Y');
        }

        if ($start->isSameMonth($end)) {
            return $start->translatedFormat('d').' - '.$end->translatedFormat('d M Y');
        }

        return $start->translatedFormat('d M Y').' - '.$end->translatedFormat('d M Y');
    }
}
