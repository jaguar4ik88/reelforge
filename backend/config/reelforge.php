<?php

return [
    /*
     * Public product name (header, emails, SEO). Override per deployment / white-label.
     * Falls back to APP_NAME, then "ReelForge".
     */
    'site_name' => env('REELFORGE_SITE_NAME', env('APP_NAME', 'ReelForge')),

    'ffmpeg_binaries'            => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'),
    'ffprobe_binaries'           => env('FFPROBE_BINARIES', '/usr/bin/ffprobe'),
    'ffmpeg_font_path'           => env('FFMPEG_FONT_PATH', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'),
    'free_plan_videos_per_month' => env('FREE_PLAN_VIDEOS_PER_MONTH', 10),
    'pro_plan_videos_per_month'  => env('PRO_PLAN_VIDEOS_PER_MONTH', 100),

    /*
     * Content paths (under the chosen disk):
     * - templates: {templates_path_prefix}/… (e.g. templates/previews/…)
     * - users:     {user_content_prefix}/{user_id}/avatars, …/projects/{id}/images, …/video.mp4
     */
    'storage' => [
        'content_disk'          => env('REELFORGE_CONTENT_DISK', env('FILESYSTEM_DISK', 'public')),
        'templates_disk'        => env('REELFORGE_TEMPLATES_DISK', env('REELFORGE_CONTENT_DISK', env('FILESYSTEM_DISK', 'public'))),
        'user_content_prefix'   => env('REELFORGE_USER_CONTENT_PREFIX', 'users'),
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
        'welcome_bonus'               => (int) env('REELFORGE_WELCOME_CREDITS', 5),
        'default_video_cost'          => (int) env('REELFORGE_DEFAULT_VIDEO_CREDIT_COST', 10),
        'default_photo_guided_cost'   => (int) env('REELFORGE_DEFAULT_PHOTO_GUIDED_CREDIT_COST', 5),
        'require_for_generation'      => filter_var(env('REELFORGE_CREDITS_REQUIRE_FOR_GENERATION', true), FILTER_VALIDATE_BOOLEAN),
        'enforce_monthly_cap'         => filter_var(env('REELFORGE_ENFORCE_MONTHLY_CAP_WITH_CREDITS', false), FILTER_VALIDATE_BOOLEAN),
        /*
         * Photo-flow UI pricing (project page: refinements + extra generations).
         * Photo: credits per generated image; Card: credits per card image; Video: fixed tiers by duration.
         */
        'photo_flow' => [
            'improvement'     => (int) env('REELFORGE_IMPROVEMENT_CREDIT_COST', 1),
            'photo_per_image' => (int) env('REELFORGE_PHOTO_CONTENT_CREDIT_PER_IMAGE', 2),
            /** Per-scene overrides for photo tab (defaults fall back to photo_per_image). */
            'photo_scene_credits' => [
                'from_wishes' => (int) env('REELFORGE_PHOTO_SCENE_FROM_WISHES_CREDITS', 2),
                'in_use'      => (int) env('REELFORGE_PHOTO_SCENE_IN_USE_CREDITS', 2),
                'studio'      => (int) env('REELFORGE_PHOTO_SCENE_STUDIO_CREDITS', 2),
            ],
            'card_per_image'  => (int) env('REELFORGE_CARD_CONTENT_CREDIT_PER_IMAGE', 1),
            'video_options'   => [
                ['seconds' => 5, 'credits' => (int) env('REELFORGE_VIDEO_5S_CREDIT_COST', 10)],
                ['seconds' => 20, 'credits' => (int) env('REELFORGE_VIDEO_20S_CREDIT_COST', 20)],
            ],
        ],
    ],
];
