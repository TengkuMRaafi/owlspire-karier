<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Enkripsi teks di database. Aman untuk data lama: baris yang masih berupa teks biasa tetap terbaca,
 * dan akan terenkripsi otomatis saat disimpan berikutnya.
 */
class EncryptedSafe implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value === null || $value === '' ? $value : Crypt::encryptString((string) $value);
    }
}
