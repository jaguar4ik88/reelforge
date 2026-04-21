<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Marketing copy is UA-heavy; English mirrors the same limits.
     *
     * Tier rules: batch photo/video per request (1,1,2,3); images capacity ≈ credits/2; videos ≈ credits/10;
     * all templates from tier 2+.
     *
     * @return array<int, array{key: string, params?: array<string, mixed>}>
     */
    private static function featuresForPlan(int $monthlyCredits, int $tier, bool $allTemplates): array
    {
        $imagesUpTo = intdiv($monthlyCredits, 2);
        $videosUpTo = intdiv($monthlyCredits, 10);
        $batch = match ($tier) {
            1, 2 => 1,
            3 => 2,
            4 => 3,
            default => 1,
        };

        $rows = [
            ['key' => 'creditsMonth', 'params' => ['count' => $monthlyCredits]],
            ['key' => 'imagesUpTo', 'params' => ['count' => $imagesUpTo]],
            ['key' => 'videosUpTo', 'params' => ['count' => $videosUpTo]],
            ['key' => 'imagesAtOnce', 'params' => ['count' => $batch]],
            ['key' => 'videosConcurrent', 'params' => ['count' => $batch]],
        ];
        if ($allTemplates) {
            $rows[] = ['key' => 'allTemplates', 'params' => []];
        } else {
            $rows[] = ['key' => 'basicTemplates', 'params' => []];
        }
        $rows[] = ['key' => 'cancelAnytime', 'params' => []];

        return $rows;
    }

    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter-monthly',
                'name' => 'Стартер',
                'description_en' => '50 credits monthly — start with catalog shots and light video. Core templates; upgrade when you scale.',
                'description_uk' => '50 кредитів на місяць — старт для карток товару та легкого відео. Базові шаблони; можна оновити тариф пізніше.',
                'monthly_credits' => 50,
                'price_cents' => 500,
                'sort_order' => 10,
                'is_featured' => false,
                'display_variant' => 'starter',
                'subscription_tier' => 1,
                'features' => self::featuresForPlan(50, 1, false),
            ],
            [
                'slug' => 'creator-monthly',
                'name' => 'Креатор',
                'description_en' => '100 credits monthly — more room for experiments and social formats. All templates.',
                'description_uk' => '100 кредитів на місяць — більше простору для тестів і соцформатів. Усі шаблони.',
                'monthly_credits' => 100,
                'price_cents' => 900,
                'sort_order' => 20,
                'is_featured' => false,
                'display_variant' => 'creator',
                'subscription_tier' => 2,
                'features' => self::featuresForPlan(100, 2, true),
            ],
            [
                'slug' => 'studio-monthly',
                'name' => 'Студія',
                'description_en' => '450 credits monthly — steady publishing rhythm for growing storefronts. Highlight tier.',
                'description_uk' => '450 кредитів на місяць — стабільний ритм публікацій для магазину, що росте.',
                'monthly_credits' => 450,
                'price_cents' => 3600,
                'sort_order' => 30,
                'is_featured' => true,
                'display_variant' => 'studio',
                'subscription_tier' => 3,
                'features' => self::featuresForPlan(450, 3, true),
            ],
            [
                'slug' => 'business-monthly',
                'name' => 'Бізнес',
                'description_en' => '1000 credits monthly — maximum throughput for teams and agencies.',
                'description_uk' => '1000 кредитів на місяць — максимальний обсяг для команд і агенцій.',
                'monthly_credits' => 1000,
                'price_cents' => 5900,
                'sort_order' => 40,
                'is_featured' => false,
                'display_variant' => 'business',
                'subscription_tier' => 4,
                'features' => self::featuresForPlan(1000, 4, true),
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
                    'subscription_tier' => $p['subscription_tier'],
                ]
            );
        }

        SubscriptionPlan::query()->whereIn('slug', ['basic-monthly', 'pro-monthly'])->update(['is_active' => false]);

        // Legacy rows still tied to old WayForPay subscriptions — keep tier in sync with former "Pro" (batch tier 2).
        SubscriptionPlan::query()->where('slug', 'pro-monthly')->update(['subscription_tier' => 2]);
    }
}
