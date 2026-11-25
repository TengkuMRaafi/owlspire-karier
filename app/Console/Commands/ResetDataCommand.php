<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ResetDataCommand extends Command
{
    protected $signature = 'owlspire:reset-data
        {--dry-run : Hanya tampilkan jumlah baris yang akan dihapus}
        {--force : Lewati pertanyaan konfirmasi}
        {--keep-logs : Jangan kosongkan berkas di storage/logs}';

    protected $description = 'Mengosongkan akun non-admin, pembayaran, riwayat ujian, aktivitas tamu, dan log sebelum deploy. Akun admin, soal, karier, belajar, paket, dan pengaturan tetap ada.';

    /** Tabel data pengguna/aktivitas, anak dulu baru induk. Bank soal, karier, belajar, paket, dan pengaturan sengaja tidak ada di sini. */
    private const TABLES = [
        'exam_attempt_answers', 'exam_attempts',
        'payment_events', 'orders', 'entitlements', 'subscriptions',
        'certificates', 'course_enrollments', 'lesson_progress',
        'chat_messages', 'chat_usages', 'ai_usages', 'interview_sessions',
        'cv_analyses', 'cv_profiles', 'job_applications', 'saved_jobs', 'job_alerts',
        'study_profiles', 'user_skills', 'telegram_links', 'user_devices',
        'daily_activities', 'audit_logs', 'email_broadcasts',
        'password_reset_tokens', 'sessions',
        'jobs', 'job_batches', 'failed_jobs',
        'wrong_question_reviews',
    ];

    public function handle(): int
    {
        $adminCount = DB::table('users')->where('role', 'admin')->count();
        if ($adminCount === 0) {
            $this->error('Tidak ada akun admin. Buat dulu dengan: php artisan owlspire:admin email@anda.com');

            return self::FAILURE;
        }

        $tables = array_values(array_filter(self::TABLES, fn (string $t) => Schema::hasTable($t)));

        $rows = [['users (selain admin)', $this->nonAdmin()->count()]];
        foreach ($tables as $table) {
            $rows[] = [$table, DB::table($table)->count()];
        }
        $this->table(['Tabel', 'Baris yang dihapus'], $rows);
        $this->line("Akun admin yang dipertahankan: {$adminCount}");

        if ($this->option('dry-run')) {
            $this->info('Dry-run: tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Hapus semua data di atas? Tidak bisa dibatalkan, pastikan database sudah di-backup.', false)) {
            $this->warn('Dibatalkan, tidak ada yang dihapus.');

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($tables) {
                foreach ($tables as $table) {
                    DB::table($table)->delete();
                }
                $this->nonAdmin()->delete();
                DB::table('users')->where('role', 'admin')->update(['last_login_at' => null, 'last_login_ip' => null]);
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        foreach ($tables as $table) {
            $this->resetAutoIncrement($table);
        }

        $this->call('cache:clear');

        if (! $this->option('keep-logs')) {
            foreach (glob(storage_path('logs/*.log')) ?: [] as $file) {
                file_put_contents($file, '');
            }
            $this->line('Berkas log dikosongkan.');
        }

        $this->info('Selesai. Sekarang hanya akun admin yang tersisa, dan log aktivitas mulai dari nol.');

        foreach (DB::table('users')->where('role', 'admin')->get(['email', 'password']) as $admin) {
            if (Hash::check('12345678', (string) $admin->password)) {
                $this->warn("Admin {$admin->email} masih memakai kata sandi bawaan lokal. Ganti sebelum deploy: php artisan owlspire:admin {$admin->email} --password=KATA_SANDI_KUAT");
            }
        }

        return self::SUCCESS;
    }

    private function nonAdmin()
    {
        return DB::table('users')->where(fn ($q) => $q->whereNull('role')->orWhere('role', '!=', 'admin'));
    }

    private function resetAutoIncrement(string $table): void
    {
        try {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            }
        } catch (\Throwable) {
            // Tabel tanpa auto-increment: abaikan.
        }
    }
}
