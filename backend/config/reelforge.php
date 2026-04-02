<?php

return [
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

    'credits' => [
        'welcome_bonus'               => (int) env('REELFORGE_WELCOME_CREDITS', 50),
        'default_video_cost'          => (int) env('REELFORGE_DEFAULT_VIDEO_CREDIT_COST', 10),
        'default_photo_guided_cost'   => (int) env('REELFORGE_DEFAULT_PHOTO_GUIDED_CREDIT_COST', 5),
        'require_for_generation'      => filter_var(env('REELFORGE_CREDITS_REQUIRE_FOR_GENERATION', true), FILTER_VALIDATE_BOOLEAN),
        'enforce_monthly_cap'         => filter_var(env('REELFORGE_ENFORCE_MONTHLY_CAP_WITH_CREDITS', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
