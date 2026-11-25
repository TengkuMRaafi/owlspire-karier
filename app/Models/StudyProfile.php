<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyProfile extends Model
{
    protected $table = 'study_profiles';

    protected $guarded = [];

    protected $casts = ['exam_date' => 'date', 'last_reminded_on' => 'date'];

    public function examType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExamType::class, 'exam_type_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
