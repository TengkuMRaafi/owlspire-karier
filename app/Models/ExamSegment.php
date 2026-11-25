<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSegment extends Model
{
    protected $table = 'exam_segments';

    protected $guarded = [];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExamType::class, 'exam_type_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'segment_id');
    }
}
