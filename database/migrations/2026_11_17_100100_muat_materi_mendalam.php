<?php

use App\Services\Learning\MateriSeeder;
use Illuminate\Database\Migrations\Migration;

/** Memasang penjelasan mendalam tiap topik belajar (details.deep) dari database/seeders/data/belajar-mendalam. */
return new class extends Migration
{
    public function up(): void
    {
        MateriSeeder::applyDeep();
    }

    public function down(): void
    {
        // Isi materi dibiarkan; tidak ada yang perlu dibatalkan.
    }
};
