<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Validation\Rule;

/** Membaca dan menyimpan preferensi pengguna berdasarkan skema di config/preferences.php. */
class Preferences
{
    /** @return array<string, array> grup => definisi (title, icon, desc, fields) */
    public function schema(): array
    {
        return config('preferences', []);
    }

    public function hasGroup(string $group): bool
    {
        return isset($this->schema()[$group]);
    }

    /** @return array<string, mixed> kunci => nilai untuk satu grup, sudah digabung dengan nilai bawaan */
    public function group(?User $user, string $group): array
    {
        $stored = (array) ($user?->preferences[$group] ?? []);
        $out = [];

        foreach ($this->schema()[$group]['fields'] ?? [] as $key => $field) {
            $out[$key] = array_key_exists($key, $stored) && $this->valid($field, $stored[$key]) ? $stored[$key] : $field['default'];
        }

        return $out;
    }

    /** Ambil satu nilai lewat "grup.kunci". Tamu (null) memakai nilai bawaan. */
    public function get(?User $user, string $path): mixed
    {
        [$group, $key] = explode('.', $path, 2) + [1 => null];

        return $this->group($user, $group)[$key] ?? null;
    }

    /** Aturan validasi untuk form satu grup (nama kolom = kunci field). */
    public function rules(string $group): array
    {
        $rules = [];
        foreach ($this->schema()[$group]['fields'] as $key => $field) {
            $rules[$key] = $field['type'] === 'bool'
                ? ['nullable', 'boolean']
                : ['required', Rule::in(array_keys($field['options']))];
        }

        return $rules;
    }

    /** Simpan satu grup. Hanya kunci yang ada di skema yang diterima. */
    public function save(User $user, string $group, array $input): void
    {
        $values = [];
        foreach ($this->schema()[$group]['fields'] as $key => $field) {
            $values[$key] = match (true) {
                $field['type'] === 'bool' => filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN),
                ($field['cast'] ?? null) === 'int' => (int) ($input[$key] ?? $field['default']),
                default => (string) ($input[$key] ?? $field['default']),
            };
        }

        $all = (array) $user->preferences;
        $all[$group] = $values;
        $user->forceFill(['preferences' => $all])->save();
    }

    /** Kembalikan satu grup ke nilai bawaan. */
    public function reset(User $user, string $group): void
    {
        $all = (array) $user->preferences;
        unset($all[$group]);
        $user->forceFill(['preferences' => $all ?: null])->save();
    }

    private function valid(array $field, mixed $value): bool
    {
        return $field['type'] === 'bool'
            ? is_bool($value)
            : is_scalar($value) && array_key_exists($value, $field['options']);
    }
}
