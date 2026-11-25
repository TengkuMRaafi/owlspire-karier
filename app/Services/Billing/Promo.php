<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Services\Settings\SiteSettings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Promo diskon paket Premium yang sedang berjalan, dipakai spanduk situs dan halaman Harga. */
class Promo
{
    /** @return Collection<int, Plan> paket aktif yang sedang diskon */
    public function plans(): Collection
    {
        try {
            return Plan::where('is_active', true)->whereNotNull('discount_type')->get()
                ->filter(fn (Plan $p) => $p->isDiscounted())->values();
        } catch (QueryException $e) {
            // Migrasi diskon belum dijalankan (atau tabel belum ada): situs tetap jalan, hanya tanpa promo.
            return collect();
        }
    }

    /** Promo paling besar potongannya, atau null bila tidak ada diskon berjalan. */
    public function current(): ?array
    {
        $plans = $this->plans();
        if ($plans->isEmpty()) {
            return null;
        }

        $best = $plans->sortByDesc(fn (Plan $p) => $p->discountPercent())->first();
        $tz = config('platform.promo.timezone', 'Asia/Jakarta');

        return [
            'plan' => $best,
            'percent' => $best->discountPercent(),
            'saving' => $best->discountAmount(),
            'multiple' => $plans->count() > 1,
            'label' => $best->discount_label ?: 'Diskon Premium',
            'starts_at' => $best->discount_starts_at?->copy()->timezone($tz),
            'ends_at' => $best->discount_ends_at?->copy()->timezone($tz),
            'url' => route('billing.index').'#paket',
        ];
    }

    /**
     * Isi spanduk situs. Tanpa pengaturan manual, isinya otomatis dari diskon paket yang sedang berjalan.
     * Bila admin mencentang banner manual, kolom yang diisi menggantikan isi otomatis (kolom kosong tetap pakai isi otomatis),
     * dan banner tetap tampil walau tidak ada diskon paket sampai waktu berakhir yang diisi admin (kosong = sampai dimatikan).
     *
     * @return array<string,mixed>|null
     */
    public function banner(): ?array
    {
        $m = app(SiteSettings::class)->promoBanner();
        $auto = $this->current();
        $tz = config('platform.promo.timezone', 'Asia/Jakarta');
        $mEnd = $m['ends_at'] ? Carbon::parse($m['ends_at'])->timezone($tz) : null;
        // Banner manual yang sudah lewat waktunya berhenti tampil sendiri.
        $manual = $m['enabled'] && ! ($mEnd && $mEnd->isPast());
        if (! $manual && ! $auto) {
            return null;
        }

        $pick = fn (string $k, string $fallback): string => ($manual && $m[$k] !== '') ? $m[$k] : $fallback;
        $b = [
            'manual' => $manual,
            'tone' => $manual ? $m['tone'] : 'sunset',
            'eyebrow' => $pick('eyebrow', 'Penawaran terbaik'),
            'headline' => $pick('headline', $auto['label'] ?? 'Diskon spesial'),
            'prefix' => $pick('prefix', ($auto['multiple'] ?? false) ? 'Hingga' : ''),
            'number' => $pick('number', $auto ? $auto['percent'].'%' : ''),
            'suffix' => $pick('suffix', 'OFF'),
            'cta' => $pick('cta', 'Ambil diskon'),
            'url' => ($manual && $m['url']) ? $m['url'] : ($auto['url'] ?? route('billing.index').'#paket'),
            'ends_at' => $manual ? $mEnd : ($auto['ends_at'] ?? null),
        ];
        // Kunci tutup-spanduk: berubah bila isi berubah, jadi banner muncul lagi untuk yang pernah menutupnya.
        $b['key'] = substr(md5(json_encode([$b['headline'], $b['number'], $b['prefix'], $b['suffix'], $b['cta'], $b['url']])), 0, 10)
            .'-'.($b['ends_at']?->timestamp ?? 0);

        return $b;
    }
}
