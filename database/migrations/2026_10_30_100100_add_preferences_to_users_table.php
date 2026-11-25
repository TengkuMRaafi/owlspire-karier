<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Preferensi pengguna disimpan sebagai JSON; daftar kunci dan nilai bawaannya ada di config/preferences.php.
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->json('preferences')->nullable());
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('preferences'));
    }
};
