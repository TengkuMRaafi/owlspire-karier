<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvProfile extends Model
{
    protected $table = 'cv_profiles';

    protected $guarded = [];

    // Teks CV berisi data pribadi (nomor HP, alamat), jadi disimpan terenkripsi.
    protected $casts = [
        'parsed' => 'array',
        'raw_text' => \App\Casts\EncryptedSafe::class,
    ];
}
