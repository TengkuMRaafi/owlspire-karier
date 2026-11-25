<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Akun yang dibuat lewat Google diberi kata sandi acak yang tidak diketahui siapa pun. Kolom ini menandai kapan pengguna
 * benar-benar membuat kata sandi sendiri, supaya akun Google bisa membuat kata sandi pertama tanpa "kata sandi saat ini".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        // Akun non-Google sudah punya kata sandi buatan sendiri.
        DB::table('users')->whereNull('google_id')->update(['password_set_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
