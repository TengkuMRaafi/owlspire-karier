<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Daftar perangkat yang sedang masuk dan keluar dari perangkat lain. Hanya bekerja dengan SESSION_DRIVER=database. */
class SessionManager
{
    public function supported(): bool
    {
        return config('session.driver') === 'database';
    }

    /** @return Collection<int, array{id:string,current:bool,ip:?string,browser:string,os:string,last_active:Carbon}> perangkat aktif, terbaru dulu */
    public function list(User $user, string $currentId): Collection
    {
        if (! $this->supported()) {
            return collect();
        }

        $lifetime = now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp();

        return DB::table($this->table())->where('user_id', $user->id)
            ->where('last_activity', '>=', $lifetime)
            ->orderByDesc('last_activity')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'current' => $row->id === $currentId,
                'ip' => $row->ip_address,
                'last_active' => Carbon::createFromTimestamp($row->last_activity),
            ] + $this->describe((string) $row->user_agent));
    }

    /** Hapus semua sesi lain milik pengguna dan ganti token "ingat saya". @return int jumlah sesi yang dihapus */
    public function revokeOthers(User $user, string $currentId): int
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (! $this->supported()) {
            return 0;
        }

        return DB::table($this->table())->where('user_id', $user->id)->where('id', '!=', $currentId)->delete();
    }

    /**
     * Sebelum login baru: sisakan ruang untuk satu sesi baru dengan mengeluarkan sesi aktif paling lama
     * bila jumlahnya sudah mencapai batas. Token \"ingat saya\" diganti supaya perangkat yang dikeluarkan
     * tidak masuk lagi lewat cookie-nya. @return int jumlah sesi yang dikeluarkan
     */
    public function enforceLimit(User $user, int $max): int
    {
        if (! $this->supported() || $max < 1) {
            return 0;
        }

        $lifetime = now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp();
        $extra = DB::table($this->table())->where('user_id', $user->id)->where('last_activity', '>=', $lifetime)
            ->orderByDesc('last_activity')->pluck('id')->slice($max - 1)->values();

        if ($extra->isEmpty()) {
            return 0;
        }

        DB::table($this->table())->whereIn('id', $extra)->delete();
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return $extra->count();
    }

    /** Hapus semua sesi pengguna (dipakai saat akun dihapus). */
    public function purge(User $user): void
    {
        if ($this->supported()) {
            DB::table($this->table())->where('user_id', $user->id)->delete();
        }
    }

    /** @return array{browser:string,os:string} */
    public function describe(string $ua): array
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Peramban',
        };
        $os = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Perangkat lain',
        };

        return ['browser' => $browser, 'os' => $os];
    }

    private function table(): string
    {
        return (string) config('session.table', 'sessions');
    }
}
