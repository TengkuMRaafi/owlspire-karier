<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Jumlah pesan chat Asisten AI yang dipakai satu pengguna pada satu hari (dasar kuota harian). */
class ChatUsage extends Model
{
    protected $table = 'chat_usages';

    protected $guarded = [];

    protected $casts = ['day' => 'date:Y-m-d'];
}
