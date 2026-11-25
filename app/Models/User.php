<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordId;
use App\Notifications\VerifyEmailId;
use App\Services\Settings\SiteSettings;
use App\Services\Account\Preferences;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_set_at' => 'datetime',
            'preferences' => 'array',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'auto_password' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** Preferensi pengguna lewat "grup.kunci" (skema di config/preferences.php), mis. $user->pref('exam.timer'). */
    public function pref(string $path): mixed
    {
        return app(Preferences::class)->get($this, $path);
    }

    protected static function booted(): void
    {
        // Sandi berubah (ganti, atur ulang, tautkan Google): sandi awal dari halaman pembayaran tidak boleh tersimpan lagi.
        static::updated(function (self $user) {
            if ($user->wasChanged('password')) {
                app(\App\Services\Billing\PurchasePassword::class)->forget($user);
            }
        });

        // Akun dihapus: gambar CV terenkripsi di disk ikut dibersihkan (barisnya di database terhapus lewat relasi).
        static::deleting(function (self $user) {
            try {
                app(\App\Services\Careers\CvVisual::class)->forget($user->id);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        // Pengaman terakhir: role admin tidak bisa diturunkan lewat jalur mana pun (UI, tinker, kode lain).
        static::updating(function (self $user) {
            if ($user->isDirty('role') && $user->getOriginal('role') === 'admin') {
                throw new \LogicException('Role admin tidak bisa diubah.');
            }
        });
    }

    /** Role: admin | staff | member (config/roles.php). Admin hanya dibuat lewat `php artisan owlspire:admin`. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /** Admin dan staf: akses penuh ke fitur pengguna, jadi tidak punya paket Gratis/Premium. */
    public function isInternal(): bool
    {
        return in_array($this->role, config('roles.internal'), true);
    }

    /** Akun ini wajib masuk lewat Google (pengguna biasa, bila aturan "Google saja" aktif). Admin dan staf tidak. */
    public function mustUseGoogle(): bool
    {
        return ! $this->isInternal() && app(\App\Services\Auth\GoogleOAuth::class)->membersGoogleOnly();
    }

    /**
     * Akun yang dibuat lewat Google atau otomatis saat beli tiket tanpa daftar, dan belum pernah membuat kata sandi
     * sendiri (kata sandinya acak, tidak diketahui siapa pun).
     */
    public function needsFirstPassword(): bool
    {
        return ($this->google_id !== null || (bool) $this->auto_password) && $this->password_set_at === null;
    }

    /**
     * Admin, staf, dan akun yang dibuat otomatis saat pembelian tidak perlu verifikasi email: pembeli sudah memegang sandi
     * dari halaman pembayaran. Pemilik email asli tetap bisa mengambil alih lewat "Lupa kata sandi".
     */
    public function skipsEmailVerification(): bool
    {
        return $this->isInternal() || (bool) $this->auto_password;
    }

    public function roleLabel(): string
    {
        return config("roles.roles.{$this->role}.label", 'Pengguna');
    }

    /**
     * Boleh membuka area panel ini? Admin mengikuti config/roles.php; akses staf ditentukan admin
     * (Pengaturan > Peran dan akses, disimpan di tabel settings) dan bawaannya sama dengan config.
     */
    public function canAccess(string $area): bool
    {
        return match ($this->role) {
            'admin' => in_array('admin', config("roles.areas.{$area}", []), true),
            'staff' => in_array($area, app(SiteSettings::class)->staffAreas(), true),
            default => false,
        };
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordId($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailId());
    }
}
