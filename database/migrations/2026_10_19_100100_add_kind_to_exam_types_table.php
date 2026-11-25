<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_types', function (Blueprint $table) {
            // selection = seleksi resmi (CPNS/BUMN/Polri-TNI), career = simulasi karier
            $table->string('kind')->default('selection')->index();
        });
    }

    public function down(): void
    {
        Schema::table('exam_types', fn (Blueprint $t) => $t->dropColumn('kind'));
    }
};
