<?php

namespace App\Services\Billing;

use App\Models\Entitlement;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Satu pintu untuk menjawab "boleh akses fitur ini atau tidak?" dan memberi akses setelah */
class EntitlementService
{
    public function userHas(int $userId, string $type, ?int $referenceId = null): bool
    {
        return Entitlement::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->when($referenceId, fn ($q) => $q->where('reference_id', $referenceId))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    /** Admin dan staf punya akses penuh tanpa paket, jadi selalu lolos pemeriksaan Premium. */
    public function hasFullAccess(?int $userId): bool
    {
        return $userId !== null && in_array(User::whereKey($userId)->value('role'), config('roles.internal'), true);
    }

    public function isPremium(?int $userId): bool
    {
        return $userId !== null && ($this->hasFullAccess($userId) || $this->userHas($userId, 'subscription'));
    }

    public function premiumUntil(int $userId): ?\Illuminate\Support\Carbon
    {
        $e = Entitlement::where('user_id', $userId)->where('type', 'subscription')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByRaw('expires_at is null desc')->orderByDesc('expires_at')->first();

        return $e?->expires_at;
    }

    /** Memberi akses setelah pesanan lunas. */
    public function grantFor(Order $order): void
    {
        if (($order->meta['granted'] ?? false) === true) {
            return;
        }
        // Pesanan tamu: hubungkan ke akun berdasarkan email (dibuat bila belum ada).
        if (! $order->user_id && ! $this->attachBuyer($order)) {
            return;
        }

        if ($order->item_type === 'plan' && $plan = Plan::find($order->item_id)) {
            $start = $this->premiumUntil($order->user_id) ?? now();
            $ends = match ($plan->interval) {
                'month' => $start->copy()->addMonth(),
                'quarter' => $start->copy()->addMonths(3),
                'half' => $start->copy()->addMonths(6),
                'year' => $start->copy()->addYear(),
                default => null,
            };

            Subscription::create(['user_id' => $order->user_id, 'plan_id' => $plan->id, 'starts_at' => now(), 'ends_at' => $ends]);
            Entitlement::create(['user_id' => $order->user_id, 'type' => 'subscription', 'reference_id' => $plan->id, 'expires_at' => $ends]);
        } elseif ($order->item_type === 'exam_pass') {
            // Satu tiket = jatah ujian penuh yang terbatas (bawaan 2x) untuk simulasi ini; habis berarti terkunci lagi.
            $this->grantExamPass($order->user_id, (int) $order->item_id, (int) ($order->meta['quantity'] ?? 1));
        } else {
            return; // TODO: item_type 'course'
        }

        $order->update(['meta' => array_merge($order->meta ?? [], ['granted' => true])]);
    }

    /** Menautkan pesanan tamu ke akun dengan email yang sama; */
    private function attachBuyer(Order $order): bool
    {
        $email = Str::lower(trim((string) ($order->meta['email'] ?? '')));
        if ($email === '') {
            return false;
        }

        $shown = trim((string) ($order->meta['display_name'] ?? ''));
        $user = User::whereRaw('lower(email) = ?', [$email])->first();
        $plain = null;

        if (! $user) {
            // Akun baru: sandi acak dibuat di sini dan ditampilkan ke pembeli di halaman tagihan (bisa disalin, lalu diganti di Pengaturan).
            $plain = PurchasePassword::generate();
            $user = new User([
                'name' => $shown !== '' ? Str::limit($shown, 40, '') : Str::limit(Str::headline(Str::before($email, '@')), 80, ''),
                'email' => $email,
                'password' => $plain,
            ]);
            $user->forceFill(['auto_password' => true]);

            try {
                $user->save();
                $user->wasRecentlyCreated = true;
            } catch (UniqueConstraintViolationException) {
                $user = User::whereRaw('lower(email) = ?', [$email])->firstOrFail(); // dibuat bersamaan oleh permintaan lain
                $plain = null;
            }
        }

        if ($plain !== null && $user->wasRecentlyCreated) {
            app(PurchasePassword::class)->remember($order, $plain);
        }

        // Pembeli baru yang memilih tidak tampil di peringkat: simpan pilihannya di akun yang baru dibuat.
        if ($user->wasRecentlyCreated && array_key_exists('show_in_ranking', $order->meta ?? []) && ! $order->meta['show_in_ranking']) {
            app(\App\Services\Account\Preferences::class)->save($user, 'privacy', ['leaderboard' => false]);
        }

        $order->update([
            'user_id' => $user->id,
            'meta' => array_merge($order->meta ?? [], ['account_created' => $user->wasRecentlyCreated]),
        ]);

        return true;
    }

    /** Jatah ujian penuh yang didapat dari satu tiket (bawaan 2x). */
    public function attemptsPerPass(): int
    {
        return max(1, (int) config('platform.exam.pass_attempts', 2));
    }

    /**
     * Tiket simulasi: satu tiket memberi jatah ujian penuh terbatas (bawaan 2x) untuk satu jenis ujian.
     * Setiap ujian penuh yang dimulai memakai 1 jatah; setelah habis simulasi itu terkunci lagi.
     * Membeli lagi menambah jatah (tiap pembelian = satu tiket baru). $quantity = jumlah tiket yang diberikan.
     */
    public function grantExamPass(int $userId, int $examTypeId, int $quantity = 1): Entitlement
    {
        $days = config('platform.exam.pass_valid_days');

        return Entitlement::create([
            'user_id' => $userId,
            'type' => 'exam_pass',
            'reference_id' => $examTypeId,
            'remaining' => $this->attemptsPerPass() * max(1, $quantity),
            'expires_at' => $days ? now()->addDays((int) $days) : null,
        ]);
    }

    /** Jumlah simulasi yang masih terbuka lewat tiket (yang jatahnya belum habis). */
    public function passCount(int $userId): int
    {
        return (int) Entitlement::query()->where('user_id', $userId)->where('type', 'exam_pass')
            ->where('remaining', '>', 0)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->distinct()->count('reference_id');
    }

    /** Premium manual dari admin (kompensasi, promo, pembayaran di luar sistem). $days null = tanpa batas waktu. */
    public function grantPremium(int $userId, ?int $days): Entitlement
    {
        if ($days === null) {
            return Entitlement::create(['user_id' => $userId, 'type' => 'subscription', 'reference_id' => null, 'expires_at' => null]);
        }

        // Bila sudah Premium tanpa batas, tidak ada yang perlu diperpanjang.
        $lifetime = Entitlement::where('user_id', $userId)->where('type', 'subscription')->whereNull('expires_at')->first();
        if ($lifetime) {
            return $lifetime;
        }

        $start = $this->premiumUntil($userId) ?? now();

        return Entitlement::create(['user_id' => $userId, 'type' => 'subscription', 'reference_id' => null, 'expires_at' => $start->copy()->addDays(max(1, $days))]);
    }

    /** Hentikan Premium sekarang juga. @return int jumlah hak yang dihentikan */
    public function revokePremium(int $userId): int
    {
        Subscription::where('user_id', $userId)->where('status', 'active')->update(['status' => 'cancelled', 'ends_at' => now()]);

        return Entitlement::where('user_id', $userId)->where('type', 'subscription')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->update(['expires_at' => now()]);
    }

    /**
     * Sisa jatah ujian penuh untuk jenis ini: PHP_INT_MAX = Premium/staf (tanpa batas),
     * selain itu jumlah jatah tiket yang belum terpakai (0 = terkunci).
     */
    public function fullAttemptsAvailable(int $userId, int $examTypeId): int
    {
        if ($this->hasFullAccess($userId) || $this->userHas($userId, 'subscription')) {
            return PHP_INT_MAX;
        }

        return (int) $this->validPasses($userId, $examTypeId)->sum('remaining');
    }

    /**
     * Mulai ujian penuh: Premium/staf selalu boleh tanpa mengurangi apa pun; pemilik tiket memakai 1 jatah.
     * Pengurangan dilakukan atomik supaya dua permintaan bersamaan tidak memakai jatah yang sama.
     */
    public function useForFullAttempt(int $userId, int $examTypeId): bool
    {
        if ($this->hasFullAccess($userId) || $this->userHas($userId, 'subscription')) {
            return true;
        }

        // Pakai tiket yang paling cepat habis masa berlakunya dulu (tanpa batas waktu paling akhir), lalu yang paling lama.
        $ids = $this->validPasses($userId, $examTypeId)
            ->orderByRaw('expires_at is null')->orderBy('expires_at')->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            if (Entitlement::whereKey($id)->where('remaining', '>', 0)->decrement('remaining') > 0) {
                return true;
            }
        }

        return false;
    }

    private function validPasses(int $userId, int $examTypeId)
    {
        return Entitlement::query()
            ->where('user_id', $userId)
            ->where('type', 'exam_pass')
            ->where('reference_id', $examTypeId)
            ->where('remaining', '>', 0)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
