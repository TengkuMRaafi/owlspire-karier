<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Akun yang dibuat otomatis saat beli tiket tanpa daftar: kata sandinya acak dan tidak diketahui siapa pun,
        // jadi pemiliknya boleh membuat kata sandi pertama tanpa "kata sandi saat ini".
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('auto_password')->default(false)->after('password_set_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('auto_password');
        });
    }
};
