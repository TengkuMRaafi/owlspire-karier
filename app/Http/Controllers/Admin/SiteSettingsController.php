<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\Audit;
use App\Services\Settings\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Fitur, kuota, pengumuman, pemeliharaan, dan akses staf: semuanya disimpan di tabel settings, bukan .env. Area `system`. */
class SiteSettingsController extends Controller
{
    public function __construct(private SiteSettings $settings)
    {
    }

    public function features(Request $request): RedirectResponse
    {
        $int = ['integer', 'min:0', 'max:100000'];
        $data = $request->validate([
            'chatbot_free_daily' => ['required', ...$int], 'chatbot_premium_daily' => ['required', ...$int],
            'interview_free_daily' => ['required', ...$int], 'interview_premium_daily' => ['required', ...$int],
            'exam_pass_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'exam_pass_valid_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'exam_trial_questions' => ['required', 'integer', 'min:1', 'max:50'],
            'maintenance_message' => ['nullable', 'string', 'max:300'],
            'announcement_text' => ['nullable', 'string', 'max:300'],
            'announcement_tone' => ['nullable', 'in:info,warning,success'],
            'announcement_url' => ['nullable', 'url', 'max:255'],
            'announcement_url_label' => ['nullable', 'string', 'max:40'],
            'promo_eyebrow' => ['nullable', 'string', 'max:30'], 'promo_headline' => ['nullable', 'string', 'max:40'],
            'promo_prefix' => ['nullable', 'string', 'max:12'], 'promo_number' => ['nullable', 'string', 'max:12'],
            'promo_suffix' => ['nullable', 'string', 'max:12'], 'promo_cta' => ['nullable', 'string', 'max:24'],
            'promo_tone' => ['nullable', 'in:sunset,violet,emerald,ocean,gold,crimson'], 'promo_url' => ['nullable', 'url', 'max:255'], 'promo_ends_at' => ['nullable', 'date'],
        ]);

        $before = $this->settings->all();

        foreach (array_keys(SiteSettings::OVERRIDES) as $key) {
            if ($key === 'learning_open') {
                $this->settings->put($key, $request->boolean('learning_open'));
            } elseif (array_key_exists($key, $data)) {
                $this->settings->put($key, $key === 'exam_pass_valid_days' && (int) $data[$key] <= 0 ? null : (int) $data[$key]);
            }
        }
        // Masa berlaku kosong/0 = tiket tidak kedaluwarsa: simpan penanda "sengaja kosong" agar tidak kembali ke bawaan.
        if (empty($data['exam_pass_valid_days'])) {
            $this->settings->put('exam_pass_valid_days', null);
        }

        $this->settings->put('registration_open', $request->boolean('registration_open'));
        $this->settings->put('maintenance', ['enabled' => $request->boolean('maintenance_enabled'), 'message' => trim((string) ($data['maintenance_message'] ?? ''))]);
        $this->settings->put('announcement', [
            'enabled' => $request->boolean('announcement_enabled'), 'text' => trim((string) ($data['announcement_text'] ?? '')),
            'tone' => $data['announcement_tone'] ?? 'info', 'url' => $data['announcement_url'] ?? null, 'url_label' => $data['announcement_url_label'] ?? null,
        ]);

        $this->settings->put('promo_banner', [
            'enabled' => $request->boolean('promo_enabled'), 'tone' => $data['promo_tone'] ?? 'sunset',
            'eyebrow' => trim((string) ($data['promo_eyebrow'] ?? '')), 'headline' => trim((string) ($data['promo_headline'] ?? '')),
            'prefix' => trim((string) ($data['promo_prefix'] ?? '')), 'number' => trim((string) ($data['promo_number'] ?? '')),
            'suffix' => trim((string) ($data['promo_suffix'] ?? '')), 'cta' => trim((string) ($data['promo_cta'] ?? '')),
            'url' => $data['promo_url'] ?? null,
            'ends_at' => filled($data['promo_ends_at'] ?? null)
                ? \Illuminate\Support\Carbon::parse($data['promo_ends_at'], config('platform.promo.timezone', 'Asia/Jakarta'))->utc()->toIso8601String()
                : null,
        ]);

        Audit::log('settings.features', null, [
            'maintenance' => $request->boolean('maintenance_enabled'), 'registration_open' => $request->boolean('registration_open'),
            'learning_open' => $request->boolean('learning_open'), 'announcement' => $request->boolean('announcement_enabled'), 'promo_banner' => $request->boolean('promo_enabled'),
            'changed_before' => array_keys($before),
        ]);

        return redirect()->to(route('admin.settings').'#fitur')->with('status', 'Pengaturan fitur dan kuota disimpan. Berlaku langsung.');
    }

    public function staffAreas(Request $request): RedirectResponse
    {
        $data = $request->validate(['areas' => ['nullable', 'array'], 'areas.*' => ['string']]);
        $areas = array_values(array_intersect($data['areas'] ?? [], config('roles.staff_grantable')));

        $this->settings->put('staff_areas', $areas);
        Audit::log('settings.staff_areas', null, ['areas' => $areas]);

        return redirect()->to(route('admin.settings').'#peran')->with('status', 'Akses staf diperbarui. Berlaku di permintaan staf berikutnya.');
    }
}
