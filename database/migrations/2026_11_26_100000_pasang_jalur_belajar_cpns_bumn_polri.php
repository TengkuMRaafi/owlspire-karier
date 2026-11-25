<?php

use App\Services\Learning\JalurResmiSeeder;
use Illuminate\Database\Migrations\Migration;

/** Menambah jalur belajar CPNS, BUMN, dan TNI/POLRI. Aman dijalankan ulang. */
return new class extends Migration
{
    public function up(): void
    {
        JalurResmiSeeder::apply();
    }

    public function down(): void
    {
    }
};
