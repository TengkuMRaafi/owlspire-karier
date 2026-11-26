<?php

use App\Services\Learning\JalurResmiSeeder;
use App\Services\Learning\MateriSeeder;
use Illuminate\Database\Migrations\Migration;

/** Mengisi penjelasan lengkap jalur CPNS, BUMN, dan TNI/POLRI dan memperbaiki format istilah. Aman diulang. */
return new class extends Migration
{
    public function up(): void
    {
        JalurResmiSeeder::apply();
        MateriSeeder::applyDeep();
    }

    public function down(): void
    {
        // Isi materi dibiarkan; tidak ada yang perlu dibatalkan.
    }
};
