<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'owlspire:admin {email : Email admin} {--name= : Nama tampilan} {--password= : Kata sandi (kosongkan untuk ditanya)}';

    protected $description = 'Membuat akun admin baru, atau menjadikan akun yang sudah ada sebagai admin.';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        $password = $this->option('password');
        if ($isNew && ! $password) {
            $password = $this->secret('Kata sandi untuk admin baru (minimal 8 karakter)');
        }
        if ($isNew && strlen((string) $password) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');

            return self::FAILURE;
        }

        $user->forceFill([
            'name' => $this->option('name') ?: ($user->name ?: 'Admin'),
            'role' => 'admin',
        ]);
        if ($password) {
            $user->password = $password; // di-hash otomatis oleh cast pada model
        }
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }
        $user->save();

        $this->info($isNew ? "Admin baru dibuat: {$email}" : "Akun {$email} sekarang berperan admin.");

        return self::SUCCESS;
    }
}
