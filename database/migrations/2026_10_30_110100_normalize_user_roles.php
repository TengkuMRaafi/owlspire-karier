<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Role disederhanakan jadi admin | staff | member; mentor dan company tidak pernah dipakai fitur mana pun. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotIn('role', ['admin', 'staff', 'member'])->update(['role' => 'member']);
    }

    public function down(): void
    {
        // Tidak ada yang dikembalikan: nilai lama (mentor/company) tidak dipakai fitur apa pun.
    }
};
