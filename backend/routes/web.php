<?php

use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'apple']);

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'apple']);

/*
 * SPA (Vite build): deploy copies frontend/dist into public/. Same document root as Laravel.
 * API stays under /api; OAuth above. Anything else → index.html for React Router.
 */
Route::fallback(function () {
    $index = public_path('index.html');
    if (!is_file($index)) {
        return response(
            'Frontend bundle missing. After npm run build, copy frontend/dist into backend/public (see scripts/deploy.sh).',
            503
        )->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    return response()->file($index);
});
