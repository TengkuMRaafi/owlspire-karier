<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSource extends Model
{
    protected $table = 'job_sources';

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function listings(): HasMany
    {
        return $this->hasMany(JobListing::class, 'job_source_id');
    }
}
