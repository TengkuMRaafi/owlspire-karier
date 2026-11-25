<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Audit\Audit;
use App\Services\Settings\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/** Ubah paket Premium: nama, harga, diskon berjangka, masa berlaku, fitur yang ditampilkan, dan aktif/nonaktif. Area `system`. */
class PlanController extends Controller
{
    private const INTERVALS = ['month' => 'Per bulan', 'quarter' => 'Per 3 bulan', 'half' => 'Per 6 bulan', 'year' => 'Per tahun'];

    private const DISCOUNT_TYPES = ['percent' => 'Persen (%)', 'amount' => 'Potongan rupiah (Rp)'];

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', [
            'plan' => $plan, 'intervals' => self::INTERVALS, 'discountTypes' => self::DISCOUNT_TYPES,
            'tz' => config('platform.promo.timezone', 'Asia/Jakarta'),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'interval' => ['nullable', 'in:'.implode(',', array_keys(self::INTERVALS))],
            'features' => ['nullable', 'string', 'max:3000'],
            'follow_quarter' => ['nullable', 'boolean'],
            'discount_type' => ['nullable', 'in:percent,amount', 'required_with:discount_value'],
            'discount_value' => ['nullable', 'integer', 'min:1', 'max:100000000', 'required_with:discount_type'],
            'discount_label' => ['nullable', 'string', 'max:60'],
            'discount_starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'discount_ends_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ], [
            'discount_type.required_with' => 'Pilih jenis diskon.',
            'discount_value.required_with' => 'Isi besar diskon.',
            'discount_starts_at.date_format' => 'Pilih tanggal dan jam mulai dari kalender.',
            'discount_ends_at.date_format' => 'Pilih tanggal dan jam berakhir dari kalender.',
        ]);

        $follow = $plan->code !== Plan::QUARTER_CODE && in_array($plan->interval, ['half', 'year'], true) && $request->boolean('follow_quarter');
        // Paket yang ikut harga 3 bulan tidak memakai diskonnya sendiri; nilainya disalin dari paket 3 bulan.
        $discount = $follow ? [] : $this->discountFields($data);

        $before = ['price' => $plan->price, 'interval' => $plan->interval, 'active' => $plan->is_active, 'discount' => $plan->discount_type ? $plan->discount_value.' '.$plan->discount_type : null];
        $plan->update([
            'name' => trim($data['name']),
            'price' => (int) $data['price'],
            'interval' => $data['interval'] ?? null,
            'features' => collect(preg_split('/\r?\n/', (string) ($data['features'] ?? '')))->map(fn ($f) => trim($f))->filter()->take(12)->values()->all(),
            'is_active' => $request->boolean('is_active'),
            'follow_quarter' => $follow,
        ] + $discount);
        Plan::syncFollowers();
        $plan->refresh();
        Audit::log('settings.plan', $plan, ['before' => $before, 'after' => ['price' => $plan->price, 'interval' => $plan->interval, 'active' => $plan->is_active, 'discount' => $plan->discount_type ? $plan->discount_value.' '.$plan->discount_type : null]]);

        return redirect()->to(route('admin.settings').'#pembayaran')->with('status', "Paket {$plan->name} diperbarui. Harga baru berlaku untuk pesanan berikutnya.");
    }

    /**
     * Kolom diskon siap simpan. Jenis kosong = diskon dihapus. Waktu diisi admin dalam zona promo, disimpan UTC.
     *
     * @return array<string, mixed>
     */
    private function discountFields(array $data, string $key = ''): array
    {
        if (empty($data['discount_type']) && empty($data['discount_value'])) {
            return ['discount_type' => null, 'discount_value' => null, 'discount_label' => null, 'discount_starts_at' => null, 'discount_ends_at' => null];
        }

        if (empty($data['discount_type'])) {
            throw ValidationException::withMessages([$key.'discount_type' => 'Pilih jenis diskon.']);
        }
        if (empty($data['discount_value'])) {
            throw ValidationException::withMessages([$key.'discount_value' => 'Isi besar diskon.']);
        }

        $value = (int) $data['discount_value'];
        $price = (int) $data['price'];

        if ($data['discount_type'] === 'percent' && $value > 90) {
            throw ValidationException::withMessages([$key.'discount_value' => 'Diskon persen maksimal 90%.']);
        }
        if ($data['discount_type'] === 'amount' && $price - $value < Plan::MIN_PAYABLE) {
            throw ValidationException::withMessages([$key.'discount_value' => 'Potongan terlalu besar. Harga akhir minimal Rp '.number_format(Plan::MIN_PAYABLE, 0, ',', '.').'.']);
        }

        $tz = config('platform.promo.timezone', 'Asia/Jakarta');
        $at = fn (?string $v) => $v ? Carbon::createFromFormat('Y-m-d\TH:i', $v, $tz)->utc() : null;
        $starts = $at($data['discount_starts_at'] ?? null);
        $ends = $at($data['discount_ends_at'] ?? null);

        if ($starts && $ends && $ends->lte($starts)) {
            throw ValidationException::withMessages([$key.'discount_ends_at' => 'Waktu berakhir harus setelah waktu mulai.']);
        }

        return [
            'discount_type' => $data['discount_type'], 'discount_value' => $value,
            'discount_label' => isset($data['discount_label']) ? trim($data['discount_label']) ?: null : null,
            'discount_starts_at' => $starts, 'discount_ends_at' => $ends,
        ];
    }

    /** Ubah harga (dan aktif/nonaktif) semua paket sekaligus dari bagian Pembayaran. */
    public function prices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plans' => ['required', 'array'],
            'plans.*.price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'plans.*.discount_type' => ['nullable', 'in:percent,amount'],
            'plans.*.discount_value' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'plans.*.discount_label' => ['nullable', 'string', 'max:60'],
            'plans.*.discount_starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'plans.*.discount_ends_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ], [
            'plans.*.discount_starts_at.date_format' => 'Pilih tanggal dan jam mulai dari kalender.',
            'plans.*.discount_ends_at.date_format' => 'Pilih tanggal dan jam berakhir dari kalender.',
        ]);

        // Periksa semua paket dulu supaya tidak ada yang tersimpan setengah-setengah bila satu diskon salah.
        $discounts = [];
        $follows = [];
        foreach (Plan::all() as $plan) {
            if (isset($data['plans'][$plan->id])) {
                $row = $data['plans'][$plan->id];
                $follows[$plan->id] = $plan->code !== Plan::QUARTER_CODE && in_array($plan->interval, ['half', 'year'], true) && ! empty($request->input("plans.{$plan->id}.follow_quarter"));
                // Paket yang ikut harga 3 bulan: diskonnya disalin dari paket 3 bulan, jadi tidak perlu diperiksa sendiri.
                $discounts[$plan->id] = $follows[$plan->id] ? [] : $this->discountFields($row, "plans.{$plan->id}.");
            }
        }

        foreach (Plan::all() as $plan) {
            if (! isset($data['plans'][$plan->id])) {
                continue;
            }
            $plan->update([
                'price' => (int) $data['plans'][$plan->id]['price'],
                'is_active' => ! empty($request->input("plans.{$plan->id}.is_active")),
                'follow_quarter' => $follows[$plan->id],
            ] + $discounts[$plan->id]);
        }

        // Harga dan diskon paket 6 bulan ikut paket 3 bulan yang baru disimpan.
        Plan::syncFollowers();

        foreach (Plan::all() as $plan) {
            if (isset($data['plans'][$plan->id])) {
                Audit::log('settings.plan', $plan, ['after' => ['price' => $plan->price, 'active' => $plan->is_active, 'follow_quarter' => $plan->follow_quarter]]);
            }
        }

        return redirect()->to(route('admin.settings').'#pembayaran')->with('status', 'Harga dan diskon paket disimpan. Berlaku untuk pesanan berikutnya.');
    }

    public function benefits()
    {
        $rows = fn (string $tier) => collect(config("platform.access.{$tier}", []))->map(fn ($r) => ($r['label'] ?? '').' | '.($r['desc'] ?? ''))->implode("\n");

        return view('admin.plans.benefits', [
            'features' => implode("\n", (array) config('platform.premium.features')),
            'freeFeatures' => implode("\n", (array) config('platform.premium.free_features')),
            'accessFree' => $rows('free'),
            'accessPremium' => $rows('premium'),
        ]);
    }

    public function updateBenefits(Request $request, SiteSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'features' => ['nullable', 'string', 'max:4000'],
            'free_features' => ['nullable', 'string', 'max:3000'],
            'access_free' => ['nullable', 'string', 'max:4000'],
            'access_premium' => ['nullable', 'string', 'max:4000'],
        ]);

        $lines = fn (?string $t, int $max) => collect(preg_split('/\r?\n/', (string) $t))->map(fn ($l) => trim($l))->filter()->take($max)->values()->all();

        // Baris tabel "Judul | Penjelasan". Ikon dan tautan dipertahankan dari baris lama dengan judul sama.
        $table = function (string $tier) use ($data, $lines): array {
            $old = collect(config("platform.access.{$tier}", []))->keyBy(fn ($r) => mb_strtolower($r['label'] ?? ''));

            return collect($lines($data["access_{$tier}"] ?? '', 12))->map(function ($line) use ($old, $tier) {
                [$label, $desc] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
                $prev = $old->get(mb_strtolower($label));

                return [
                    'icon' => $prev['icon'] ?? ($tier === 'premium' ? 'crown' : 'check'),
                    'label' => mb_substr($label, 0, 80), 'desc' => mb_substr($desc, 0, 220),
                    'route' => $prev['route'] ?? 'account.subscription',
                ];
            })->filter(fn ($r) => $r['label'] !== '')->values()->all();
        };

        $settings->put('premium_features', $lines($data['features'] ?? '', 12));
        $settings->put('premium_free_features', $lines($data['free_features'] ?? '', 10));
        $settings->put('access_free', $table('free'));
        $settings->put('access_premium', $table('premium'));
        Audit::log('settings.benefits', null, ['premium' => count($lines($data['features'] ?? '', 12))]);

        return redirect()->route('admin.plans.benefits')->with('status', 'Keuntungan Premium disimpan. Langsung tampil di halaman Harga dan Langganan.');
    }
}
