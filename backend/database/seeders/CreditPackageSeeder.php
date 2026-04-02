<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['slug' => 'starter', 'name' => 'Starter', 'credits_amount' => 50, 'price_cents' => 499, 'sort_order' => 10],
            ['slug' => 'creator', 'name' => 'Creator', 'credits_amount' => 200, 'price_cents' => 1499, 'sort_order' => 20],
            ['slug' => 'studio', 'name' => 'Studio', 'credits_amount' => 500, 'price_cents' => 2999, 'sort_order' => 30],
        ];

        foreach ($packages as $p) {
            CreditPackage::query()->updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'name'           => $p['name'],
                    'credits_amount' => $p['credits_amount'],
                    'price_cents'    => $p['price_cents'],
                    'currency'       => 'USD',
                    'is_active'      => true,
                    'sort_order'     => $p['sort_order'],
                ]
            );
        }
    }
}
