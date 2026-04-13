<?php

return [
    'name'            => env('APP_NAME', 'ReelForge'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => (bool) env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'http://localhost'),
    // OAuth callback redirect (SocialAuthController). Prefer FRONTEND_URL; if unset, APP_URL (same-origin prod).
    // Local dev: set FRONTEND_URL=http://localhost:5173 when SPA is on Vite and API on :8000.
    'frontend_url'    => env('FRONTEND_URL') ?: env('APP_URL', 'http://localhost:5173'),
    'timezone'        => env('APP_TIMEZONE', 'UTC'),
    'locale'          => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale'    => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher'          => 'AES-256-CBC',
    'key'             => env('APP_KEY'),
    'previous_keys'   => array_filter(explode(',', env('APP_PREVIOUS_KEYS', ''))),
    'maintenance'     => ['driver' => 'file'],
    'providers'       => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
];
