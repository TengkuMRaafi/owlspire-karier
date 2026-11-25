<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkill extends Model
{
    protected $table = 'user_skills';

    protected $guarded = [];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Skill::class, 'skill_id');
    }
}
