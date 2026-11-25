<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramLink extends Model
{
    protected $table = 'telegram_links';

    protected $guarded = [];

    protected $casts = ['linked_at' => 'datetime'];
}
