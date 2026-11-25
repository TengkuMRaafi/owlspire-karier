<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kolom keamanan akun: penangguhan, jejak login, paksa ganti sandi, dan verifikasi 2 langkah (TOTP). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->index();
            $table->string('suspended_reason', 255)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->text('two_factor_secret')->nullable();          // terenkripsi (cast di model)
            $table->text('two_factor_recovery_codes')->nullable();  // terenkripsi, berisi hash kode pemulihan
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'suspended_reason', 'last_login_at', 'last_login_ip', 'must_change_password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
