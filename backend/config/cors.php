<?php

$origins = env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173');
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', (string) $origins))));

return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
