<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan yang bisa diubah admin dari panel (tabel `settings`), tanpa menyentuh .env.
 * Nilai yang ada di OVERRIDES langsung menimpa config() saat aplikasi berjalan, jadi kode lain tidak perlu diubah.
 */
class SiteSettings
{
    private const CACHE_KEY = 'site_settings.v1';

    /** kunci pengaturan => [kunci config, tipe]. Hanya kunci di sini yang boleh ditimpa dari panel. */
    public const OVERRIDES = [
        'learning_open' => ['platform.learning_open', 'bool'],
        'chatbot_free_daily' => ['platform.chatbot.free_daily', 'int'],
        'chatbot_premium_daily' => ['platform.chatbot.premium_daily', 'int'],
        'interview_free_daily' => ['platform.ai_tools.interview.free_daily', 'int'],
        'interview_premium_daily' => ['platform.ai_tools.interview.premium_daily', 'int'],
        'exam_pass_price' => ['platform.exam.pass_price', 'int'],
        'exam_pass_valid_days' => ['platform.exam.pass_valid_days', 'nullable_int'],
        'exam_trial_questions' => ['platform.exam.trial_questions_per_segment', 'int'],
        // Keuntungan Premium yang diedit admin: daftar teks dan baris tabel Gratis vs Premium.
        'premium_features' => ['platform.premium.features', 'list'],
        'premium_free_features' => ['platform.premium.free_features', 'list'],
        'access_free' => ['platform.access.free', 'rows'],
        'access_premium' => ['platform.access.premium', 'rows'],
    ];

    private ?array $all = null;

    /** @return array<string,mixed> */
    public function all(): array
    {
        if ($this->all !== null) {
            return $this->all;
        }

        try {
            return $this->all = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
        } catch (\Throwable) {
            return $this->all = []; // tabel belum ada (mis. sebelum migrasi): pakai bawaan dan jangan menyimpan hasil kosong
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    public function put(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->flush();
    }

    public function forget(string $key): void
    {
        Setting::whereKey($key)->delete();
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->all = null;
        $this->applyOverrides();
    }

    /** Terapkan nilai tersimpan ke config(). Dipanggil saat boot dan setiap kali pengaturan berubah. */
    public function applyOverrides(): void
    {
        foreach (self::OVERRIDES as $key => [$configKey, $type]) {
            $value = $this->get($key);
            if ($value === null && ! ($type === 'nullable_int' && array_key_exists($key, $this->all()))) {
                continue;
            }
            if (in_array($type, ['list', 'rows'], true) && empty($value)) {
                continue; // kosong = pakai bawaan
            }
            config([$configKey => match ($type) {
                'list' => array_values(array_filter(array_map(fn ($x) => trim((string) $x), (array) $value), fn ($x) => $x !== '')),
                'rows' => array_values(array_filter((array) $value, 'is_array')),
                'bool' => (bool) $value,
                'nullable_int' => $value === null || $value === '' || (int) $value <= 0 ? null : (int) $value,
                default => max(0, (int) $value),
            }]);
        }
    }

    /** Area panel yang boleh dibuka staf. Bawaan: yang tertulis untuk staf di config/roles.php. */
    public function staffAreas(): array
    {
        $default = collect(config('roles.areas'))->filter(fn ($roles) => in_array('staff', $roles, true))->keys()->all();
        $saved = $this->get('staff_areas');
        $areas = is_array($saved) ? $saved : $default;

        // Hanya area yang memang boleh diberikan ke staf; Dashboard selalu ada supaya staf tidak terkunci dari panel.
        return array_values(array_unique(array_merge(['dashboard'], array_intersect($areas, config('roles.staff_grantable')))));
    }

    /** Peran yang efektif boleh membuka sebuah area (admin sesuai config, staf sesuai pilihan admin). */
    public function rolesFor(string $area): array
    {
        $roles = in_array('admin', config("roles.areas.{$area}", []), true) ? ['admin'] : [];
        if (in_array($area, $this->staffAreas(), true)) {
            $roles[] = 'staff';
        }

        return $roles;
    }

    public function registrationOpen(): bool
    {
        return (bool) $this->get('registration_open', true);
    }

    /** @return array{enabled:bool,message:string} */
    public function maintenance(): array
    {
        $m = (array) $this->get('maintenance', []);

        return [
            'enabled' => (bool) ($m['enabled'] ?? false),
            'message' => (string) ($m['message'] ?? 'Kami sedang melakukan pemeliharaan singkat. Silakan kembali beberapa saat lagi.'),
        ];
    }

    /** @return array{enabled:bool,text:string,tone:string,url:?string,url_label:?string} */
    public function announcement(): array
    {
        $a = (array) $this->get('announcement', []);
        $tone = $a['tone'] ?? 'info';

        return [
            'enabled' => (bool) ($a['enabled'] ?? false) && filled($a['text'] ?? null),
            'text' => (string) ($a['text'] ?? ''),
            'tone' => in_array($tone, ['info', 'warning', 'success'], true) ? $tone : 'info',
            'url' => $a['url'] ?? null,
            'url_label' => $a['url_label'] ?? null,
        ];
    }

    /** @return array{enabled:bool,tone:string,eyebrow:string,headline:string,prefix:string,number:string,suffix:string,cta:string,url:?string,ends_at:?string} */
    public function promoBanner(): array
    {
        $p = (array) $this->get('promo_banner', []);
        $tone = $p['tone'] ?? 'sunset';
        $t = fn (string $k): string => trim((string) ($p[$k] ?? ''));

        return [
            'enabled' => (bool) ($p['enabled'] ?? false),
            'tone' => in_array($tone, ['sunset', 'violet', 'emerald', 'ocean', 'gold', 'crimson'], true) ? $tone : 'sunset',
            'eyebrow' => $t('eyebrow'), 'headline' => $t('headline'), 'prefix' => $t('prefix'),
            'number' => $t('number'), 'suffix' => $t('suffix'), 'cta' => $t('cta'),
            'url' => $t('url') !== '' ? $t('url') : null,
            'ends_at' => $t('ends_at') !== '' ? $t('ends_at') : null,
        ];
    }
}
