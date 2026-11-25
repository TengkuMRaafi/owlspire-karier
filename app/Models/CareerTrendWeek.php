<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Arsip papan karier mingguan; baris minggu terbaru yang dibaca landing. */
class CareerTrendWeek extends Model
{
    protected $table = 'career_trend_weeks';

    protected $guarded = [];

    protected $casts = ['per_source' => 'array', 'score' => 'float'];
}
