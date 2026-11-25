<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $table = 'lessons';

    protected $guarded = [];

    protected $casts = [
        'is_free_preview' => 'boolean',
        'is_approved' => 'boolean',
        'details' => 'array',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function isVideo(): bool
    {
        return $this->provider === 'youtube' && $this->video_id;
    }

    /** Pencarian YouTube biasa (semua lisensi) untuk topik tanpa video terpasang. */
    public static function searchUrl(string $query): string
    {
        return 'https://www.youtube.com/results?search_query='.rawurlencode($query);
    }

    public function thumbnailUrl(): ?string
    {
        return $this->isVideo() ? 'https://i.ytimg.com/vi/'.$this->video_id.'/hqdefault.jpg' : null;
    }

    /** Pencarian YouTube yang dibatasi lisensi Creative Commons (sp=EgIwAQ==). */
    public static function ccSearchUrl(string $query): string
    {
        return 'https://www.youtube.com/results?search_query='.rawurlencode($query).'&sp='.rawurlencode('EgIwAQ==');
    }
}
