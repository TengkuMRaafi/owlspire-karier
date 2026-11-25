<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\CareerSeeder;
use Database\Seeders\ExamSeeder;
use Database\Seeders\LearningSeeder;
use App\Services\Exam\QuestionBankLoader;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Jenis ujian + bank soal awal (aman dijalankan berulang).
        $this->call(ExamSeeder::class);
        // 15 karier + simulasi + soal, jalur belajar per karier, dan paket Premium.
        $this->call([CareerSeeder::class, LearningSeeder::class, PlanSeeder::class]);

        // Bank soal lengkap (ribuan soal). Dilewati saat pengujian otomatis supaya tes tetap cepat.
        if (! app()->runningUnitTests()) {
            $r = app(QuestionBankLoader::class)->loadAll();
            $this->command?->info("Bank soal lengkap dimuat: {$r['created']} baru, {$r['updated']} diperbarui.");
        }

        // Akun admin awal HANYA di lingkungan lokal.
        if (app()->environment('local')) {
            $admin = User::firstOrNew(['email' => 'admin@owlspire.com']);
            if (! $admin->exists) {
                $admin->forceFill(['name' => 'Admin Owlspire', 'role' => 'admin', 'email_verified_at' => now()]);
                $admin->password = '12345678';
                $admin->save();
                $this->command?->warn('Admin lokal dibuat: admin@owlspire.com / 12345678 (ganti sebelum dipakai di server).');
            }
        }
    }
}
