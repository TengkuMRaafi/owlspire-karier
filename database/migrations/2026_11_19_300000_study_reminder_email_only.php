<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Pengingat belajar kini hanya lewat email: pengguna yang tadinya memilih Telegram dipindah ke email. */
    public function up(): void
    {
        DB::table('study_profiles')->where('remind_channel', 'telegram')->update(['remind_channel' => 'email']);
    }

    public function down(): void
    {
        // tidak ada yang perlu dikembalikan
    }
};
