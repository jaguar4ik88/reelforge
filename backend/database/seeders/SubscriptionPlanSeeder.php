<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter-monthly',
                'name' => 'Starter',
                'description_en' => 'Individual creators and light use — core templates and a focused monthly credit pool.',
                'description_uk' => 'Для індивідуальних авторів і невеликого навантаження — базові шаблони та фіксований місячний пул кредитів.',
                'monthly_credits' => 100,
                'price_cents' => 900,
                'sort_order' => 10,
                'is_featured' => false,
                'display_variant' => 'starter',
                'features' => [
                    ['key' => 'creditsMonth', 'params' => ['count' => 100]],
                    ['key' => 'videosUpTo', 'params' => ['count' => 10]],
                    ['key' => 'imagesAtOnce', 'params' => ['count' => 2]],
                    ['key' => 'videosConcurrent', 'params' => ['count' => 1]],
                    ['key' => 'basicTemplates', 'params' => []],
                    ['key' => 'cancelAnytime', 'params' => []],
                ],
            ],
            [
                'slug' => 'pro-monthly',
                'name' => 'Pro',
                'description_en' => 'Growing channels: more credits, parallel renders, and all templates.',
                'description_uk' => 'Для тих, хто масштабується: більше кредитів, паралельні генерації та усі шаблони.',
                'monthly_credits' => 350,
                'price_cents' => 2400,
                'sort_order' => 20,
                'is_featured' => true,
                'display_variant' => 'pro',
                'features' => [
                    ['key' => 'creditsMonth', 'params' => ['count' => 350]],
                    ['key' => 'videosUpTo', 'params' => ['count' => 35]],
                    ['key' => 'imagesAtOnce', 'params' => ['count' => 5]],
                    ['key' => 'videosConcurrent', 'params' => ['count' => 2]],
                    ['key' => 'allTemplates', 'params' => []],
                    ['key' => 'prioritySupport', 'params' => []],
                    ['key' => 'cancelAnytime', 'params' => []],
                ],
            ],
            [
                'slug' => 'business-monthly',
                'name' => 'Business',
                'description_en' => 'Teams and agencies: maximum credits, premium templates, and priority support.',
                'description_uk' => 'Для команд і агенцій: максимум кредитів, преміум-шаблони та пріоритетна підтримка.',
                'monthly_credits' => 1000,
                'price_cents' => 5900,
                'sort_order' => 30,
                'is_featured' => false,
                'display_variant' => 'business',
                'features' => [
                    ['key' => 'creditsMonth', 'params' => ['count' => 1000]],
                    ['key' => 'videosUpTo', 'params' => ['count' => 100]],
                    ['key' => 'imagesAtOnce', 'params' => ['count' => 7]],
                    ['key' => 'videosConcurrent', 'params' => ['count' => 3]],
                    ['key' => 'allPremiumTemplates', 'params' => []],
                    ['key' => 'prioritySupport', 'params' => []],
                    ['key' => 'noWatermark', 'params' => []],
                    ['key' => 'cancelAnytime', 'params' => []],
                ],
            ],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'name' => $p['name'],
                    'description_en' => $p['description_en'],
                    'description_uk' => $p['description_uk'],
                    'monthly_credits' => $p['monthly_credits'],
                    'features' => $p['features'],
                    'price_cents' => $p['price_cents'],
                    'currency' => 'USD',
                    'is_active' => true,
                    'is_featured' => $p['is_featured'],
                    'display_variant' => $p['display_variant'],
                    'sort_order' => $p['sort_order'],
                ]
            );
        }

        SubscriptionPlan::query()->where('slug', 'basic-monthly')->update(['is_active' => false]);
    }
}
