<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $table = 'questions';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'is_trial' => 'boolean',
    ];

    /** Alamat publik gambar soal, atau null bila soal tidak bergambar. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('uploads/questions/'.$this->image) : null;
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(ExamSegment::class, 'segment_id');
    }
}
