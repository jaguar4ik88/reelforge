<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PhotoGuidedProjectController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\ImageController;
use App\Http\Controllers\API\TemplateController;
use App\Http\Controllers\API\VideoController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\CreditsController;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

Route::get('/credits/packages', [CreditsController::class, 'packages']);
Route::get('/credits/costs', [CreditsController::class, 'costs']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Projects
    Route::post('projects/from-photo', [PhotoGuidedProjectController::class, 'store']);
    Route::post('projects/{project}/photo-generations', [PhotoGuidedProjectController::class, 'startGeneration']);
    Route::apiResource('projects', ProjectController::class)->except(['update']);

    // Images
    Route::post('/projects/{project}/images', [ImageController::class, 'upload']);
    Route::delete('/projects/{project}/images/{image}', [ImageController::class, 'destroy']);

    // Video generation
    Route::post('/projects/{project}/generate', [VideoController::class, 'generate']);

    // Templates (read-only for users)
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::get('/templates/{template}', [TemplateController::class, 'show']);

    Route::get('/credits/balance', [CreditsController::class, 'balance']);
    Route::get('/credits/transactions', [CreditsController::class, 'transactions']);
    Route::post('/credits/purchase-stub/{slug}', [CreditsController::class, 'purchaseStub']);

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/',               [ProfileController::class, 'show']);
        Route::post('/',              [ProfileController::class, 'update']);
        Route::post('/password',      [ProfileController::class, 'changePassword']);
        Route::get('/stats',          [ProfileController::class, 'stats']);
    });
});
