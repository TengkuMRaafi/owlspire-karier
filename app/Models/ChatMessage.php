<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu pesan di chat Asisten AI: dari pengguna (user) atau jawaban asisten (assistant). */
class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $guarded = [];
}
