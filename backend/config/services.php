<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'replicate' => [
        'token' => env('REPLICATE_API_TOKEN'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        /** Vision model for product photo analysis (JSON output). */
        'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
        /** Wishes → English enrichment (fallback when Anthropic is not set). */
        'wishes_model' => env('OPENAI_WISHES_MODEL', 'gpt-4o-mini'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        /** Claude model for wishes translation / enrichment. */
        'model' => env('ANTHROPIC_WISHES_MODEL', 'claude-sonnet-4-20250514'),
    ],

    'apple' => [
        'client_id'     => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect'      => env('APPLE_REDIRECT_URI'),
        'team_id'       => env('APPLE_TEAM_ID'),
        'key_id'        => env('APPLE_KEY_ID'),
        // Relative to backend base_path(); absolute paths (/) or Windows drive paths work as-is.
        'private_key'   => (function () {
            $path = env('APPLE_PRIVATE_KEY');
            if ($path === null || $path === '') {
                return null;
            }
            $path = trim($path);
            if (str_starts_with($path, '/')) {
                return $path;
            }
            if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
                return $path;
            }

            return base_path($path);
        })(),
        'passphrase'    => env('APPLE_PASSPHRASE'),
    ],

];
