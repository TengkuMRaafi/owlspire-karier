<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/** Membuat paket Premium dari config('platform.premium'). */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('platform.premium.plans', []) as $p) {
            Plan::firstOrCreate(['code' => $p['code']], [
                'name' => $p['name'], 'price' => $p['price'], 'interval' => $p['interval'],
                'features' => config('platform.premium.features'), 'is_active' => true,
                'follow_quarter' => (bool) ($p['follow_quarter'] ?? false),
            ]);
        }
        Plan::syncFollowers();
    }
}
