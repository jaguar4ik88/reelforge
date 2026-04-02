<?php

namespace Database\Seeders;

use App\Models\CreditCost;
use Illuminate\Database\Seeder;

class CreditCostSeeder extends Seeder
{
    public function run(): void
    {
        CreditCost::query()->updateOrCreate(
            ['operation_key' => 'video_generation'],
            ['cost' => (int) config('reelforge.credits.default_video_cost', 10)]
        );

        CreditCost::query()->updateOrCreate(
            ['operation_key' => 'photo_guided_generation'],
            ['cost' => (int) config('reelforge.credits.default_photo_guided_cost', 5)]
        );
    }
}
