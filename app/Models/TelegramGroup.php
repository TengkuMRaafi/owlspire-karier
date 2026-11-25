<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramGroup extends Model
{
    public const ZONES = ['wib' => 'Asia/Jakarta', 'wita' => 'Asia/Makassar', 'wit' => 'Asia/Jayapura'];
    public const OFFSETS = ['wib' => 'UTC+7', 'wita' => 'UTC+8', 'wit' => 'UTC+9'];
    public const HOURS = [6, 18]; // pukul 06.00 dan 18.00 waktu setempat

    protected $table = 'telegram_groups';

    protected $guarded = [];

    protected $casts = ['positions' => 'array', 'skills' => 'array', 'locations' => 'array', 'sent_urls' => 'array', 'is_active' => 'boolean'];

    /** Kunci zona yang valid; grup yang baru dibuat belum membawa nilai bawaan dari database. */
    public function zoneKey(): string
    {
        return isset(self::ZONES[(string) $this->zone]) ? (string) $this->zone : 'wib';
    }

    /** Baris yang sama dipakai untuk grup (chat_id negatif) dan chat pribadi pengguna (chat_id positif). */
    public function isPrivate(): bool
    {
        return (int) $this->chat_id > 0;
    }

    /** Kata ganti untuk pesan: "Zona waktu grup" atau "Zona waktu kamu". */
    public function who(): string
    {
        return $this->isPrivate() ? 'kamu' : 'grup';
    }

    public function timezone(): string
    {
        return self::ZONES[$this->zoneKey()];
    }

    public function zoneLabel(): string
    {
        return strtoupper($this->zoneKey());
    }

    public function offsetLabel(): string
    {
        return self::OFFSETS[$this->zoneKey()];
    }
}
