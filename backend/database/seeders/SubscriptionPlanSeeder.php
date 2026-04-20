<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'starter-monthly', 'name' => 'Starter', 'monthly_credits' => 100, 'price_cents' => 900, 'sort_order' => 10],
            ['slug' => 'pro-monthly', 'name' => 'Pro', 'monthly_credits' => 350, 'price_cents' => 2400, 'sort_order' => 20],
            ['slug' => 'business-monthly', 'name' => 'Business', 'monthly_credits' => 1000, 'price_cents' => 5900, 'sort_order' => 30],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'name' => $p['name'],
                    'monthly_credits' => $p['monthly_credits'],
                    'price_cents' => $p['price_cents'],
                    'currency' => 'USD',
                    'is_active' => true,
                    'sort_order' => $p['sort_order'],
                ]
            );
        }

        SubscriptionPlan::query()->where('slug', 'basic-monthly')->update(['is_active' => false]);
    }
}
