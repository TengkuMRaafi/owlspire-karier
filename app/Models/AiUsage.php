<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    protected $table = 'ai_usages';

    protected $guarded = [];

    public $timestamps = false;

    // Kolom day sengaja tanpa cast: nilainya string Y-m-d polos supaya pencarian dan unique cocok di SQLite maupun MySQL.
}
