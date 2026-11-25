<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Plan extends Model
{
    /** Harga bayar terendah setelah diskon (rupiah), supaya tagihan QRIS tidak jadi nol. */
    public const MIN_PAYABLE = 1000;

    protected $table = 'plans';

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'follow_quarter' => 'boolean',
        'discount_value' => 'integer',
        'discount_starts_at' => 'datetime',
        'discount_ends_at' => 'datetime',
    ];

    /** Diskon diatur admin dan berlaku hanya di dalam rentang waktunya (kosong = tanpa batas). */
    public function isDiscounted(?Carbon $at = null): bool
    {
        $at ??= now();

        if (! in_array($this->discount_type, ['percent', 'amount'], true) || (int) $this->discount_value <= 0) {
            return false;
        }
        if ($this->discount_starts_at && $this->discount_starts_at->gt($at)) {
            return false;
        }
        if ($this->discount_ends_at && $this->discount_ends_at->lt($at)) {
            return false;
        }

        return $this->discountAmount() > 0;
    }

    /** Diskon sudah diatur tetapi belum mulai. */
    public function isDiscountScheduled(?Carbon $at = null): bool
    {
        $at ??= now();

        return in_array($this->discount_type, ['percent', 'amount'], true) && (int) $this->discount_value > 0
            && $this->discount_starts_at && $this->discount_starts_at->gt($at);
    }

    /** Potongan dalam rupiah, tanpa melihat rentang waktu. Tidak pernah membuat harga di bawah MIN_PAYABLE. */
    public function discountAmount(): int
    {
        $price = (int) $this->price;
        $raw = match ($this->discount_type) {
            'percent' => (int) floor($price * min(100, (int) $this->discount_value) / 100),
            'amount' => (int) $this->discount_value,
            default => 0,
        };

        return max(0, min($raw, $price - self::MIN_PAYABLE));
    }

    /** Persen hemat yang ditampilkan (dibulatkan ke bawah). */
    public function discountPercent(): int
    {
        return $this->price > 0 ? (int) floor($this->discountAmount() / $this->price * 100) : 0;
    }

    /** Harga yang harus dibayar sekarang. */
    public function finalPrice(?Carbon $at = null): int
    {
        return (int) $this->price - ($this->isDiscounted($at) ? $this->discountAmount() : 0);
    }

    /** Kode paket 3 bulan yang jadi acuan harga paket 6 bulan. */
    public const QUARTER_CODE = 'premium-3m';

    /**
     * Paket yang diatur "ikut harga 3 bulan" (kolom follow_quarter) mengambil harga dan diskon dari paket 3 bulan,
     * dikali lama langganannya (6 bulan = 2x). Dipanggil setiap kali admin menyimpan harga.
     */
    public static function syncFollowers(): void
    {
        $quarter = static::where('code', static::QUARTER_CODE)->first();
        if (! $quarter) {
            return;
        }

        $factor = ['half' => 2, 'year' => 4];

        foreach (static::where('follow_quarter', true)->where('id', '!=', $quarter->id)->get() as $plan) {
            $x = $factor[$plan->interval] ?? null;
            if (! $x) {
                continue;
            }
            $hasDiscount = in_array($quarter->discount_type, ['percent', 'amount'], true) && (int) $quarter->discount_value > 0;
            $plan->update([
                'price' => (int) $quarter->price * $x,
                'discount_type' => $hasDiscount ? $quarter->discount_type : null,
                'discount_value' => $hasDiscount ? ($quarter->discount_type === 'amount' ? (int) $quarter->discount_value * $x : (int) $quarter->discount_value) : null,
                'discount_label' => $hasDiscount ? $quarter->discount_label : null,
                'discount_starts_at' => $hasDiscount ? $quarter->discount_starts_at : null,
                'discount_ends_at' => $hasDiscount ? $quarter->discount_ends_at : null,
            ]);
        }
    }
}
