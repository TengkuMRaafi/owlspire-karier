<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/** Aturan kata sandi satu tempat: minimal 8, ada huruf dan angka, dan (di produksi) belum pernah bocor. */
class PasswordRules
{
    public static function default(): Password
    {
        $rule = Password::min(8)->letters()->numbers();

        return app()->environment('production') ? $rule->uncompromised(3) : $rule;
    }

    public static function messages(): array
    {
        return [
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.letters' => 'Kata sandi harus mengandung huruf.',
            'password.numbers' => 'Kata sandi harus mengandung angka.',
            'password.uncompromised' => 'Kata sandi ini pernah muncul di kebocoran data publik. Pilih yang lain.',
            'password.different' => 'Kata sandi baru harus berbeda dari yang lama.',
        ];
    }
}
