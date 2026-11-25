<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hitungan kuota chat per pengguna per hari, terpisah dari riwayat supaya menghapus riwayat tidak mereset kuota.
        Schema::create('chat_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('day');
            $t->unsignedInteger('count')->default(0);
            $t->timestamps();

            $t->unique(['user_id', 'day']);
        });

        // Pesan yang sudah terkirim hari ini sebelum tabel ini ada tetap dihitung.
        $today = now()->startOfDay();
        $rows = DB::table('chat_messages')->where('role', 'user')->where('created_at', '>=', $today)
            ->selectRaw('user_id, count(*) as total')->groupBy('user_id')->get();
        foreach ($rows as $r) {
            DB::table('chat_usages')->insert(['user_id' => $r->user_id, 'day' => $today->toDateString(), 'count' => $r->total, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_usages');
    }
};
