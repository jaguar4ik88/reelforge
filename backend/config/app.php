<?php

return [
    'name' => env('APP_NAME', 'ReelForge'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    // OAuth / WayForPay returnUrl. If FRONTEND_URL is unset: local → Vite :5173 (not APP_URL :8000, or SPA never loads).
    // Production: falls back to APP_URL (same-origin deploy with built SPA in public/).
    'frontend_url' => rtrim((string) (env('FRONTEND_URL') ?: (env('APP_ENV') === 'local'
        ? 'http://localhost:5173'
        : env('APP_URL', 'http://localhost'))), '/'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => array_filter(explode(',', env('APP_PREVIOUS_KEYS', ''))),
    'maintenance' => ['driver' => 'file'],
    'providers' => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
];
