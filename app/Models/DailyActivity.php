<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyActivity extends Model
{
    protected $table = 'daily_activities';

    protected $guarded = [];

    // Kolom day sengaja tanpa cast: nilainya string Y-m-d polos supaya pencarian dan unique cocok di SQLite maupun MySQL.
}
