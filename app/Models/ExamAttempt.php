<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $table = 'exam_attempts';

    protected $guarded = [];

    protected $casts = [
        'score' => 'array',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'deadline_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExamType::class, 'exam_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class, 'attempt_id')->orderBy('id');
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /** Sisa waktu resmi (detik), dihitung server dari deadline_at. */
    public function remainingSeconds(): int
    {
        if (! $this->deadline_at) {
            return PHP_INT_MAX;
        }

        return max(0, (int) now()->diffInSeconds($this->deadline_at, false));
    }

    public function isOverdue(): bool
    {
        return $this->deadline_at !== null && now()->greaterThan($this->deadline_at);
    }

    /** Lama pengerjaan dalam detik (null bila belum selesai). */
    public function durationSeconds(): ?int
    {
        return $this->finished_at ? max(0, (int) $this->started_at->diffInSeconds($this->finished_at)) : null;
    }
}
