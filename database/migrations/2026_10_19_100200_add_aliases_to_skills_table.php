<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', fn (Blueprint $t) => $t->json('aliases')->nullable());
    }

    public function down(): void
    {
        Schema::table('skills', fn (Blueprint $t) => $t->dropColumn('aliases'));
    }
};
