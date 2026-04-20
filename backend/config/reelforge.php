<?php

return [
    /*
     * Public product name (header, emails, SEO). Override per deployment / white-label.
     * Falls back to APP_NAME, then "ReelForge".
     */
    'site_name' => env('REELFORGE_SITE_NAME', env('APP_NAME', 'ReelForge')),

    /** SPA origin — keep in sync with config('app.frontend_url'). No trailing slash. */
    'frontend_url' => rtrim((string) (env('FRONTEND_URL') ?: (env('APP_ENV') === 'local'
        ? 'http://localhost:5173'
        : env('APP_URL', 'http://localhost'))), '/'),

    'ffmpeg_binaries' => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'),
    'ffprobe_binaries' => env('FFPROBE_BINARIES', '/usr/bin/ffprobe'),
    'ffmpeg_font_path' => env('FFMPEG_FONT_PATH', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'),
    'free_plan_videos_per_month' => env('FREE_PLAN_VIDEOS_PER_MONTH', 10),
    'pro_plan_videos_per_month' => env('PRO_PLAN_VIDEOS_PER_MONTH', 100),

    /*
     * Content paths (under the chosen disk):
     * - templates: {templates_path_prefix}/… (e.g. templates/previews/…)
     * - users:     {user_content_prefix}/{user_id}/avatars, …/projects/{id}/images, …/video.mp4
     */
    'storage' => [
        'content_disk' => env('REELFORGE_CONTENT_DISK', env('FILESYSTEM_DISK', 'public')),
        'templates_disk' => env('REELFORGE_TEMPLATES_DISK', env('REELFORGE_CONTENT_DISK', env('FILESYSTEM_DISK', 'public'))),
        'user_content_prefix' => env('REELFORGE_USER_CONTENT_PREFIX', 'users'),
        'templates_path_prefix' => env('REELFORGE_TEMPLATES_PATH_PREFIX', 'templates'),
    ],

    /*
     * Product images: max length of the longer side after upload (px). Reduces storage and future vision token cost.
     * Requires PHP GD extension for resizing.
     */
    'image_max_dimension' => (int) env('REELFORGE_IMAGE_MAX_DIMENSION', 1024),

    /*
     * Photo-guided generation: translate/expand user wishes via Claude/OpenAI before FLUX.
     * MVP: keep false — users type wishes in English; set true when ANTHROPIC_API_KEY or OPENAI is used for enrichment.
     */
    'photo_guided' => [
        'wishes_enrichment' => filter_var(env('REELFORGE_WISHES_ENRICHMENT', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'credits' => [
        'welcome_bonus' => (int) env('REELFORGE_WELCOME_CREDITS', 10),
        'default_video_cost' => (int) env('REELFORGE_DEFAULT_VIDEO_CREDIT_COST', 10),
        'default_photo_guided_cost' => (int) env('REELFORGE_DEFAULT_PHOTO_GUIDED_CREDIT_COST', 5),
        'require_for_generation' => filter_var(env('REELFORGE_CREDITS_REQUIRE_FOR_GENERATION', true), FILTER_VALIDATE_BOOLEAN),
        'enforce_monthly_cap' => filter_var(env('REELFORGE_ENFORCE_MONTHLY_CAP_WITH_CREDITS', false), FILTER_VALIDATE_BOOLEAN),
        /*
         * Photo-flow UI pricing (project page: refinements + extra generations).
         * Photo: credits per generated image; Card: credits per card image; Video: fixed tiers by duration.
         */
        'photo_flow' => [
            'improvement' => (int) env('REELFORGE_IMPROVEMENT_CREDIT_COST', 1),
            'photo_per_image' => (int) env('REELFORGE_PHOTO_CONTENT_CREDIT_PER_IMAGE', 2),
            /** Per-scene overrides for photo tab (defaults fall back to photo_per_image). */
            'photo_scene_credits' => [
                'from_wishes' => (int) env('REELFORGE_PHOTO_SCENE_FROM_WISHES_CREDITS', 2),
                'in_use' => (int) env('REELFORGE_PHOTO_SCENE_IN_USE_CREDITS', 2),
                'studio' => (int) env('REELFORGE_PHOTO_SCENE_STUDIO_CREDITS', 2),
            ],
            'card_per_image' => (int) env('REELFORGE_CARD_CONTENT_CREDIT_PER_IMAGE', 1),
            'video_options' => [
                ['seconds' => 5, 'credits' => (int) env('REELFORGE_VIDEO_5S_CREDIT_COST', 10)],
                ['seconds' => 20, 'credits' => (int) env('REELFORGE_VIDEO_20S_CREDIT_COST', 20)],
            ],
        ],
    ],

    /*
     * Payments: default billing = FastSpring (EU / global). Ukraine + WayForPay flag → WayForPay.
     */
    'payments' => [
        /** When true and request country is UA, prefer WayForPay (if WayForPay is also enabled). */
        'wayforpay_for_ukraine_enabled' => filter_var(env('WAYFORPAY_FOR_UKRAINE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

        /**
         * When true, all regions use WayForPay checkout if WAYFORPAY_* is configured (ignores UA-only rule).
         * Use for WayForPay-only deployments.
         */
        'wayforpay_billing_global' => filter_var(env('WAYFORPAY_BILLING_GLOBAL', false), FILTER_VALIDATE_BOOLEAN),

        'wayforpay' => [
            'enabled' => filter_var(env('WAYFORPAY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'merchant_account' => env('WAYFORPAY_MERCHANT_ACCOUNT', ''),
            'secret_key' => env('WAYFORPAY_SECRET_KEY', ''),
            'merchant_domain_name' => env('WAYFORPAY_MERCHANT_DOMAIN_NAME', ''),
            'pay_url' => env('WAYFORPAY_PAY_URL', 'https://secure.wayforpay.com/pay'),
            /** UAH per 1 USD for converting package USD price at checkout. */
            'usd_to_uah' => (float) env('WAYFORPAY_USD_TO_UAH', 42.0),
            /** Extra discount for UA checkout (percent, e.g. 5 = 5%). */
            'ua_discount_percent' => (float) env('WAYFORPAY_UA_DISCOUNT_PERCENT', 0),
        ],

        'fastspring' => [
            'enabled' => filter_var(env('FASTSPRING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            /** Session / Seller API host (override only if FastSpring support gives a different base). */
            'api_base_url' => rtrim((string) env('FASTSPRING_API_BASE_URL', 'https://api.fastspring.com'), '/'),
            'api_username' => env('FASTSPRING_API_USERNAME', ''),
            'api_password' => env('FASTSPRING_API_PASSWORD', ''),
            /** e.g. your-store-id/main — see FastSpring checkout path in dashboard. */
            'checkout_path' => env('FASTSPRING_CHECKOUT_PATH', ''),
            'live' => filter_var(env('FASTSPRING_LIVE', true), FILTER_VALIDATE_BOOLEAN),
            /** HMAC secret from FastSpring webhook configuration (Message Security). */
            'webhook_hmac_secret' => env('FASTSPRING_WEBHOOK_HMAC_SECRET', ''),
            /**
             * Append ?catalog= to the session checkout URL so “Continue shopping” targets your SPA (Classic-compatible param).
             * FastSpring generally does not HTTP-redirect buyers to your site automatically after payment; use SpringBoard thank-you / completion settings if you need a prominent link.
             */
            'append_catalog_to_checkout_url' => filter_var(env('FASTSPRING_APPEND_CATALOG_TO_CHECKOUT_URL', true), FILTER_VALIDATE_BOOLEAN),
            /** Path under FRONTEND_URL for post-checkout continuation (query string allowed). */
            'checkout_return_path' => ltrim((string) env('FASTSPRING_CHECKOUT_RETURN_PATH', 'app/credits?payment=fastspring_return'), '/'),
            /**
             * Map CreditPackage.slug → FastSpring catalog product path (per product in FastSpring).
             */
            'credit_package_products' => array_filter([
                'trial-50' => env('FASTSPRING_PRODUCT_TRIAL_50', ''),
                'start-175' => env('FASTSPRING_PRODUCT_START_175', ''),
                'pro-450' => env('FASTSPRING_PRODUCT_PRO_450', ''),
                'max-1500' => env('FASTSPRING_PRODUCT_MAX_1500', ''),
            ], fn ($v) => is_string($v) && $v !== ''),
        ],
    ],
];
