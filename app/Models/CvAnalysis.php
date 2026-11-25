<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvAnalysis extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'cv_analyses';

    protected $guarded = [];

    protected $casts = ['result' => 'array', 'created_at' => 'datetime'];
}
