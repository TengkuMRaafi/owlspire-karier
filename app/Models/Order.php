<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class, 'order_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
