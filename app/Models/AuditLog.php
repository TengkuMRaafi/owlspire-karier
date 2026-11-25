<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $guarded = [];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    /** Kelompok aksi untuk filter dan label di halaman Log aktivitas. */
    public const GROUPS = [
        'login' => 'Masuk dan keamanan',
        'user' => 'Pengguna',
        'order' => 'Pesanan',
        'settings' => 'Pengaturan sistem',
        'content' => 'Konten',
        'broadcast' => 'Broadcast email',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function group(): string
    {
        return explode('.', $this->action)[0];
    }

    public function label(): string
    {
        // Kunci berisi titik (login.success), jadi baca dari array penuh, bukan lewat notasi titik config().
        return (config('audit.actions') ?? [])[$this->action] ?? $this->action;
    }
}
