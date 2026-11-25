<?php

namespace App\Models;

use App\Models\Skill;
use App\Models\ExamType;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Satu karier/pekerjaan populer. */
class Career extends Model
{
    protected $table = 'careers';

    protected $guarded = [];

    protected $casts = ['details' => 'array', 'is_active' => 'boolean'];

    public function scopeListed(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('rank')->orderBy('id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'career_skill', 'career_id', 'skill_id');
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class, 'exam_type_id');
    }

    public function course(): HasOne
    {
        return $this->hasOne(Course::class, 'career_id');
    }

    public function detail(string $key, mixed $default = null): mixed
    {
        return $this->details[$key] ?? $default;
    }
}
