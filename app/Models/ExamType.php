<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    protected $table = 'exam_types';

    protected $guarded = [];

    protected $casts = [
        'passing_rules' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeSelection(Builder $q): Builder
    {
        return $q->where('kind', 'selection');
    }

    public function scopeCareer(Builder $q): Builder
    {
        return $q->where('kind', 'career');
    }

    public function isCareer(): bool
    {
        return $this->kind === 'career';
    }

    public function segments(): HasMany
    {
        return $this->hasMany(ExamSegment::class, 'exam_type_id')->orderBy('position')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'exam_type_id');
    }
}
