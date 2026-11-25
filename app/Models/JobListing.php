<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobListing extends Model
{
    protected $table = 'job_listings';

    protected $guarded = [];

    protected $casts = [
        'remote' => 'boolean',
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(JobSource::class, 'job_source_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Skill::class, 'job_skill', 'job_listing_id');
    }
}
