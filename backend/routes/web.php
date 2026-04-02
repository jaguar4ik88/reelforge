<?php

use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'apple']);

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'apple']);
