<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'basic-monthly', 'name' => 'Basic', 'monthly_credits' => 100, 'price_cents' => 999, 'sort_order' => 10],
            ['slug' => 'pro-monthly', 'name' => 'Pro', 'monthly_credits' => 300, 'price_cents' => 2499, 'sort_order' => 20],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'name'             => $p['name'],
                    'monthly_credits'  => $p['monthly_credits'],
                    'price_cents'      => $p['price_cents'],
                    'currency'         => 'USD',
                    'is_active'        => true,
                    'sort_order'       => $p['sort_order'],
                ]
            );
        }
    }
}
