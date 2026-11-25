<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'courses';

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'is_premium' => 'boolean',
    ];

    public function career(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Career::class, 'career_id');
    }

    public function lessons(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, CourseSection::class, 'course_id', 'section_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class, 'course_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Skill::class, 'course_skill');
    }
}
