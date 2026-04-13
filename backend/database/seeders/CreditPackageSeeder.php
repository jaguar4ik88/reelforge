<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['slug' => 'trial-50', 'name' => 'Credits 50', 'credits_amount' => 50, 'price_cents' => 599, 'sort_order' => 10],
            ['slug' => 'start-175', 'name' => 'Credits 150 + 25 bonus', 'credits_amount' => 175, 'price_cents' => 2499, 'sort_order' => 20],
            ['slug' => 'pro-450', 'name' => 'Credits 350 + 100 bonus', 'credits_amount' => 450, 'price_cents' => 4999, 'sort_order' => 30],
            ['slug' => 'max-1500', 'name' => 'Credits 1000 + 500 bonus', 'credits_amount' => 1500, 'price_cents' => 9999, 'sort_order' => 40],
        ];

        foreach ($packages as $p) {
            CreditPackage::query()->updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'name' => $p['name'],
                    'credits_amount' => $p['credits_amount'],
                    'price_cents' => $p['price_cents'],
                    'currency' => 'USD',
                    'is_active' => true,
                    'sort_order' => $p['sort_order'],
                ]
            );
        }

        CreditPackage::query()->whereIn('slug', ['topup-50', 'starter', 'creator', 'studio'])->update(['is_active' => false]);
    }
}
