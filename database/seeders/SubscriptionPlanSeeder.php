<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('subscription_plans')->updateOrInsert(
            ['slug' => 'verified-basic'],
            [
                'name' => 'Verified Basic',
                'price' => 1500.00,
                'currency' => 'NGN',
                'duration_days' => 30,
                'features' => json_encode([
                    'blue_tick' => true,
                    'priority_display' => true,
                    'verified_label' => true,
                    'analytics' => 'basic'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
