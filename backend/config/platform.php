<?php

/**
 * App-level business config (credits, storage paths, photo flow).
 * Environment variables: prefer APP_PLATFORM_*; legacy REELFORGE_* still work if set.
 */
return [
    'site_name' => env('APP_PLATFORM_SITE_NAME', env('REELFORGE_SITE_NAME', env('APP_NAME', 'App'))),

    'seller' => [
        'company_name' => env('SELLER_COMPANY_NAME', env('REELFORGE_SELLER_COMPANY_NAME', '')),
        'tax_id' => env('SELLER_TAX_ID', env('REELFORGE_SELLER_TAX_ID', '')),
        'legal_address' => env('SELLER_LEGAL_ADDRESS', env('REELFORGE_SELLER_LEGAL_ADDRESS', '')),
        'physical_address' => env('SELLER_PHYSICAL_ADDRESS', env('REELFORGE_SELLER_PHYSICAL_ADDRESS', '')),
        'phone' => env('SELLER_PHONE', env('REELFORGE_SELLER_PHONE', '')),
        'email' => env('SELLER_EMAIL', env('REELFORGE_SELLER_EMAIL', '')),
    ],

    'frontend_url' => rtrim((string) (env('FRONTEND_URL') ?: (env('APP_ENV') === 'local'
        ? 'http://localhost:5173'
        : env('APP_URL', 'http://localhost'))), '/'),

    /** Set to false to block new signups (email + OAuth) while existing users can sign in. */
    'registration_enabled' => filter_var(env('APP_PLATFORM_REGISTRATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'ffmpeg_binaries' => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'),
    'ffprobe_binaries' => env('FFPROBE_BINARIES', '/usr/bin/ffprobe'),
    'ffmpeg_font_path' => env('FFMPEG_FONT_PATH', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'),
    'free_plan_videos_per_month' => env('FREE_PLAN_VIDEOS_PER_MONTH', 10),
    'pro_plan_videos_per_month' => env('PRO_PLAN_VIDEOS_PER_MONTH', 100),

    'storage' => [
        'content_disk' => env('APP_PLATFORM_CONTENT_DISK', env('REELFORGE_CONTENT_DISK', env('FILESYSTEM_DISK', 'public'))),
        'templates_disk' => env('APP_PLATFORM_TEMPLATES_DISK', env('REELFORGE_TEMPLATES_DISK', env('APP_PLATFORM_CONTENT_DISK', env('REELFORGE_CONTENT_DISK', env('FILESYSTEM_DISK', 'public'))))),
        'user_content_prefix' => env('APP_PLATFORM_USER_CONTENT_PREFIX', env('REELFORGE_USER_CONTENT_PREFIX', 'users')),
        'templates_path_prefix' => env('APP_PLATFORM_TEMPLATES_PATH_PREFIX', env('REELFORGE_TEMPLATES_PATH_PREFIX', 'templates')),
    ],

    'image_max_dimension' => (int) env('APP_PLATFORM_IMAGE_MAX_DIMENSION', env('REELFORGE_IMAGE_MAX_DIMENSION', 1024)),

    'photo_guided' => [
        'wishes_enrichment' => filter_var(env('APP_PLATFORM_WISHES_ENRICHMENT', env('REELFORGE_WISHES_ENRICHMENT', false)), FILTER_VALIDATE_BOOLEAN),
        'video_i2v' => [
            'model_id' => env('APP_PLATFORM_VIDEO_I2V_MODEL', env('REELFORGE_VIDEO_I2V_MODEL')) ?: 'aicapcut/stable-video-diffusion-img2vid-xt-optimized:7b595c69ca428904c1907155b93a5580653d1e9dcd407612142595908650dd67',
            'num_inference_steps' => (int) (env('APP_PLATFORM_VIDEO_I2V_STEPS', env('REELFORGE_VIDEO_I2V_STEPS')) ?: 25),
            'num_frames' => (int) (env('APP_PLATFORM_VIDEO_I2V_FRAMES', env('REELFORGE_VIDEO_I2V_FRAMES')) ?: 25),
            'width' => (int) (env('APP_PLATFORM_VIDEO_I2V_WIDTH', env('REELFORGE_VIDEO_I2V_WIDTH')) ?: 0),
            'height' => (int) (env('APP_PLATFORM_VIDEO_I2V_HEIGHT', env('REELFORGE_VIDEO_I2V_HEIGHT')) ?: 0),
        ],
        'card_photo_analysis' => [
            'openai_fallback' => filter_var(env('APP_PLATFORM_CARD_ANALYSIS_OPENAI_FALLBACK', env('REELFORGE_CARD_ANALYSIS_OPENAI_FALLBACK', true)), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

    /**
     * Product card (new architecture): Kontext scene regen → vision typography → PHP text overlay.
     * Only env toggle: APP_PLATFORM_CARD_PHP_COMPOSITE. All other behaviour is defined here (no card-specific .env keys).
     */
    'card' => [
        'php_composite' => [
            'enabled' => filter_var(env('APP_PLATFORM_CARD_PHP_COMPOSITE', false), FILTER_VALIDATE_BOOLEAN),
            'width' => 1080,
            'height' => 1080,
            /** Legacy T2I backdrop: prompts.models key (flux-schnell / flux-dev). */
            'replicate_model_key' => 'preview',
            /** Longer side (px) for 16:9 / 9:16 etc. when using aspect_ratio from the UI. */
            'long_edge' => 1080,
            'default_accent' => '#d4af37',
            'auto_text_contrast' => true,
            'local_text_contrast' => true,
            'infographic_callouts' => false,
            /**
             * true: FLUX Kontext full scene → vision → PHP text. false: empty T2I backdrop + optional product paste.
             */
            'regen_scene_before_text' => true,
            'scene_regen_model_key' => 'kontext',
            'vision_typography_from_scene' => true,
            'vision_overlay_analysis' => true,
            'text_halo_on_light_patch' => true,
            'diagonal_accents' => false,
            /** Legacy path only: overlay reference as product layer. */
            'composite_product_layer' => true,
            'font_bold' => 'Montserrat-Bold.ttf',
            'font_regular' => 'Montserrat-Regular.ttf',
        ],

        /**
         * HTML + Puppeteer screenshot for on-card typography (replaces Imagick/GD text overlay when enabled).
         */
        'puppeteer' => [
            'enabled' => filter_var(env('APP_PLATFORM_CARD_PUPPETEER', false), FILTER_VALIDATE_BOOLEAN),
            'node_binary' => env('APP_PLATFORM_NODE_BINARY', 'node'),
            /** Absolute path to cli.mjs; empty = base_path('card-renderer/cli.mjs'). */
            'cli_path' => env('APP_PLATFORM_CARD_PUPPETEER_CLI', ''),
            /** Optional: system Chrome for puppeteer-core style setups. */
            'executable_path' => env('PUPPETEER_EXECUTABLE_PATH', ''),
            'device_scale_factor' => (int) env('APP_PLATFORM_CARD_PUPPETEER_DPR', 2),
            'jpeg_quality' => (int) env('APP_PLATFORM_CARD_PUPPETEER_JPEG_QUALITY', 92),
        ],
    ],

    'credits' => [
        'welcome_bonus' => (int) env('APP_PLATFORM_WELCOME_CREDITS', env('REELFORGE_WELCOME_CREDITS', 10)),
        'default_video_cost' => (int) env('APP_PLATFORM_DEFAULT_VIDEO_CREDIT_COST', env('REELFORGE_DEFAULT_VIDEO_CREDIT_COST', 10)),
        'default_photo_guided_cost' => (int) env('APP_PLATFORM_DEFAULT_PHOTO_GUIDED_CREDIT_COST', env('REELFORGE_DEFAULT_PHOTO_GUIDED_CREDIT_COST', 5)),
        'require_for_generation' => filter_var(env('APP_PLATFORM_CREDITS_REQUIRE_FOR_GENERATION', env('REELFORGE_CREDITS_REQUIRE_FOR_GENERATION', true)), FILTER_VALIDATE_BOOLEAN),
        'enforce_monthly_cap' => filter_var(env('APP_PLATFORM_ENFORCE_MONTHLY_CAP_WITH_CREDITS', env('REELFORGE_ENFORCE_MONTHLY_CAP_WITH_CREDITS', false)), FILTER_VALIDATE_BOOLEAN),
        'photo_flow' => [
            'improvement' => (int) env('APP_PLATFORM_IMPROVEMENT_CREDIT_COST', env('REELFORGE_IMPROVEMENT_CREDIT_COST', 1)),
            'photo_per_image' => (int) env('APP_PLATFORM_PHOTO_CONTENT_CREDIT_PER_IMAGE', env('REELFORGE_PHOTO_CONTENT_CREDIT_PER_IMAGE', 2)),
            'photo_scene_credits' => [
                'from_wishes' => (int) env('APP_PLATFORM_PHOTO_SCENE_FROM_WISHES_CREDITS', env('REELFORGE_PHOTO_SCENE_FROM_WISHES_CREDITS', 2)),
                'no_watermark' => (int) env(
                    'APP_PLATFORM_PHOTO_SCENE_NO_WATERMARK',
                    env('APP_PLATFORM_PHOTO_SCENE_IN_USE_CREDITS', env('REELFORGE_PHOTO_SCENE_IN_USE_CREDITS', 2))
                ),
                'studio' => (int) env('APP_PLATFORM_PHOTO_SCENE_STUDIO_CREDITS', env('REELFORGE_PHOTO_SCENE_STUDIO_CREDITS', 2)),
            ],
            /** GPT Image card from product + reference (Product card: «Приклад», «Шаблон»). */
            'card_by_example' => (int) env('APP_PLATFORM_CARD_BY_EXAMPLE_CREDITS', env('REELFORGE_CARD_BY_EXAMPLE_CREDITS', 2)),
            /** Prompt / photo-guided product card (вкладка «Промпт», generation з content_type card). */
            'card_by_prompt' => (int) env(
                'APP_PLATFORM_CARD_BY_PROMPT_CREDITS',
                env(
                    'REELFORGE_CARD_BY_PROMPT_CREDITS',
                    env('APP_PLATFORM_CARD_CONTENT_CREDIT_PER_IMAGE', env('REELFORGE_CARD_CONTENT_CREDIT_PER_IMAGE', 1))
                )
            ),
            'video_options' => [
                ['seconds' => 5, 'credits' => (int) env('APP_PLATFORM_VIDEO_5S_CREDIT_COST', env('REELFORGE_VIDEO_5S_CREDIT_COST', 10))],
                ['seconds' => 20, 'credits' => (int) env('APP_PLATFORM_VIDEO_20S_CREDIT_COST', env('REELFORGE_VIDEO_20S_CREDIT_COST', 20))],
            ],
        ],
        'photo_guided_video' => [
            'min_balance' => (int) env('APP_PLATFORM_PHOTO_GUIDED_VIDEO_MIN_BALANCE', env('REELFORGE_PHOTO_GUIDED_VIDEO_MIN_BALANCE', 10)),
            'blocked_plan_slugs' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('APP_PLATFORM_PHOTO_GUIDED_VIDEO_BLOCKED_PLANS', env('REELFORGE_PHOTO_GUIDED_VIDEO_BLOCKED_PLANS', 'starter-monthly')))
            ))),
        ],
    ],

    'payments' => [
        'wayforpay_for_ukraine_enabled' => filter_var(env('WAYFORPAY_FOR_UKRAINE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'wayforpay_billing_global' => filter_var(env('WAYFORPAY_BILLING_GLOBAL', false), FILTER_VALIDATE_BOOLEAN),

        'wayforpay' => [
            'enabled' => filter_var(env('WAYFORPAY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'merchant_account' => env('WAYFORPAY_MERCHANT_ACCOUNT', ''),
            'secret_key' => env('WAYFORPAY_SECRET_KEY', ''),
            'merchant_domain_name' => env('WAYFORPAY_MERCHANT_DOMAIN_NAME', ''),
            'pay_url' => env('WAYFORPAY_PAY_URL', 'https://secure.wayforpay.com/pay'),
            'usd_to_uah' => (float) env('WAYFORPAY_USD_TO_UAH', 42.0),
            'ua_discount_percent' => (float) env('WAYFORPAY_UA_DISCOUNT_PERCENT', 0),
        ],

        'fastspring' => [
            'enabled' => filter_var(env('FASTSPRING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'api_base_url' => rtrim((string) env('FASTSPRING_API_BASE_URL', 'https://api.fastspring.com'), '/'),
            'api_username' => env('FASTSPRING_API_USERNAME', ''),
            'api_password' => env('FASTSPRING_API_PASSWORD', ''),
            'checkout_path' => env('FASTSPRING_CHECKOUT_PATH', ''),
            'live' => filter_var(env('FASTSPRING_LIVE', true), FILTER_VALIDATE_BOOLEAN),
            'webhook_hmac_secret' => env('FASTSPRING_WEBHOOK_HMAC_SECRET', ''),
            'append_catalog_to_checkout_url' => filter_var(env('FASTSPRING_APPEND_CATALOG_TO_CHECKOUT_URL', true), FILTER_VALIDATE_BOOLEAN),
            'checkout_return_path' => ltrim((string) env('FASTSPRING_CHECKOUT_RETURN_PATH', 'app/credits?payment=fastspring_return'), '/'),
            'credit_package_products' => array_filter([
                'trial-50' => env('FASTSPRING_PRODUCT_TRIAL_50', ''),
                'start-175' => env('FASTSPRING_PRODUCT_START_175', ''),
                'pro-450' => env('FASTSPRING_PRODUCT_PRO_450', ''),
                'max-1500' => env('FASTSPRING_PRODUCT_MAX_1500', ''),
            ], fn ($v) => is_string($v) && $v !== ''),
        ],
    ],
];
