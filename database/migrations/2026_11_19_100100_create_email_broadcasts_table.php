<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Broadcast email dari admin (jadwal seleksi, diskon, karier baru, umum) + penanda pengguna yang berhenti menerima info. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // pengirim (admin)
            $table->string('kind', 20)->default('umum');       // jadwal | diskon | karier | umum
            $table->string('subject', 160);                    // subjek di inbox
            $table->string('title', 160);                      // judul di dalam email
            $table->text('body');
            $table->string('highlight', 60)->nullable();       // diskon: kode promo
            $table->text('detail')->nullable();                // jadwal/diskon/karier: satu baris per butir
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->string('audience', 20)->default('all');    // all | premium | free
            $table->string('status', 20)->default('draft')->index(); // draft | queued | sending | sent
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('broadcast_opt_out_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('broadcast_opt_out_at');
        });
        Schema::dropIfExists('email_broadcasts');
    }
};
