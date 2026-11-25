<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Bila aktif, harga dan diskon paket ini otomatis mengikuti paket Premium 3 bulan (6 bulan = 2x).
            $table->boolean('follow_quarter')->default(false)->after('interval');
        });

        // Pastikan paket 6 bulan ada di basis data yang sudah berjalan, lalu ikutkan ke harga 3 bulan.
        $quarter = Plan::where('code', Plan::QUARTER_CODE)->first();
        $half = Plan::firstOrCreate(['code' => 'premium-6m'], [
            'name' => 'Premium 6 Bulan', 'price' => $quarter ? (int) $quarter->price * 2 : 138000, 'interval' => 'half',
            'features' => config('platform.premium.features'), 'is_active' => true,
        ]);
        $half->update(['follow_quarter' => true]);
        Plan::syncFollowers();
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('follow_quarter');
        });
    }
};
