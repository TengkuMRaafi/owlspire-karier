<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu kali broadcast email dari admin. Isi ditulis sekali; templat email yang sama dipakai untuk semua jenis. */
class EmailBroadcast extends Model
{
    protected $table = 'email_broadcasts';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'recipients_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public const KINDS = [
        'jadwal' => 'Jadwal seleksi',
        'diskon' => 'Diskon',
        'karier' => 'Karier baru',
        'umum' => 'Umum',
    ];

    public const AUDIENCES = [
        'all' => 'Semua pengguna',
        'premium' => 'Hanya Premium',
        'free' => 'Hanya non-Premium',
    ];

    public const STATUSES = [
        'draft' => 'Draf',
        'queued' => 'Mengantre',
        'sending' => 'Mengirim',
        'sent' => 'Terkirim',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Dipakai Audit::log sebagai label objek. */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->subject);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? self::KINDS['umum'];
    }

    public function audienceLabel(): string
    {
        return self::AUDIENCES[$this->audience] ?? self::AUDIENCES['all'];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** @return list<string> baris detail yang tidak kosong (maks 12) */
    public function detailLines(): array
    {
        $lines = preg_split('/\R/u', (string) $this->detail) ?: [];

        return array_slice(array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== '')), 0, 12);
    }

    /** Jadwal: "Pendaftaran: 1 - 10 Desember" jadi label + nilai. @return list<array{label: ?string, value: string}> */
    public function scheduleRows(): array
    {
        return array_map(function (string $line) {
            if (str_contains($line, ':')) {
                [$label, $value] = array_map('trim', explode(':', $line, 2));
                if ($label !== '' && $value !== '') {
                    return ['label' => $label, 'value' => $value];
                }
            }

            return ['label' => null, 'value' => $line];
        }, $this->detailLines());
    }

    /** Karier: "Software Engineer | PT Contoh, Pekanbaru" jadi judul + keterangan. @return list<array{title: string, sub: ?string}> */
    public function jobItems(): array
    {
        return array_map(function (string $line) {
            [$title, $sub] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);

            return ['title' => $title, 'sub' => $sub ?: null];
        }, $this->detailLines());
    }

    /** @return list<string> paragraf isi (dipisah baris kosong) */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', trim((string) $this->body)) ?: [])));
    }

    /** Catat hasil satu email; bila semua sudah diproses, tandai broadcast selesai. */
    public function recordResult(bool $ok): void
    {
        static::whereKey($this->getKey())->increment($ok ? 'sent_count' : 'failed_count');
        $this->finishIfDone();
    }

    /** Penerima yang ternyata sudah berhenti/tidak valid saat giliran kirim: keluarkan dari hitungan. */
    public function skipOne(): void
    {
        static::whereKey($this->getKey())->where('recipients_count', '>', 0)->decrement('recipients_count');
        $this->finishIfDone();
    }

    public function finishIfDone(): void
    {
        $fresh = static::find($this->getKey());
        if ($fresh && $fresh->status === 'sending' && ($fresh->sent_count + $fresh->failed_count) >= $fresh->recipients_count) {
            static::whereKey($fresh->getKey())->where('status', 'sending')->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }
}
