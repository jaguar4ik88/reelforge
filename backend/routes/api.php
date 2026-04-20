<?php

use App\Http\Controllers\API\Admin\AdminSubscriptionPlanController;
use App\Http\Controllers\API\Admin\AdminTemplateController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CreditsController;
use App\Http\Controllers\API\FastSpringController;
use App\Http\Controllers\API\GenerationController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\ImageController;
use App\Http\Controllers\API\PhotoGuidedProjectController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\TemplateController;
use App\Http\Controllers\API\WayForPayController;
use App\Services\Payments\FastSpringService;
use App\Services\Payments\WayForPayService;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/credits/packages', [CreditsController::class, 'packages']);
Route::get('/credits/subscription-plans', [CreditsController::class, 'subscriptionPlans']);
Route::get('/credits/costs', [CreditsController::class, 'costs']);

/** Public SPA config (brand name per deployment / domain). */
Route::get('/site', function () {
    $wfp = app(WayForPayService::class);
    $fs = app(FastSpringService::class);

    return response()->json([
        'success' => true,
        'data' => [
            'site_name' => config('reelforge.site_name'),
            'seller' => config('reelforge.seller'),
            'payments' => [
                'default_provider' => filter_var(config('reelforge.payments.wayforpay_billing_global', false), FILTER_VALIDATE_BOOLEAN) && $wfp->enabled()
                    ? 'wayforpay'
                    : 'fastspring',
                'wayforpay_for_ukraine_enabled' => filter_var(config('reelforge.payments.wayforpay_for_ukraine_enabled', true), FILTER_VALIDATE_BOOLEAN),
                'wayforpay_billing_global' => filter_var(config('reelforge.payments.wayforpay_billing_global', false), FILTER_VALIDATE_BOOLEAN),
                'wayforpay_enabled' => $wfp->enabled(),
                'fastspring_enabled' => $fs->enabled(),
                'display_currency' => 'USD',
                'usd_to_uah' => (float) config('reelforge.payments.wayforpay.usd_to_uah', 42),
                'ua_discount_percent' => (float) config('reelforge.payments.wayforpay.ua_discount_percent', 0),
            ],
        ],
    ]);
});

Route::post('/payments/wayforpay/callback', [WayForPayController::class, 'callback']);
Route::post('/payments/fastspring/webhook', [FastSpringController::class, 'webhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/home', HomeController::class);

    // Projects (photo-guided AI flow only)
    Route::post('projects/from-photo', [PhotoGuidedProjectController::class, 'store']);
    Route::post('projects/{project}/product-analysis', [PhotoGuidedProjectController::class, 'analyzeProduct']);
    Route::post('projects/{project}/photo-generations', [PhotoGuidedProjectController::class, 'startGeneration']);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'show', 'update', 'destroy']);

    Route::middleware('staff')->prefix('admin')->group(function () {
        Route::get('templates', [AdminTemplateController::class, 'index']);
        Route::post('templates', [AdminTemplateController::class, 'store']);
        Route::get('templates/{template}', [AdminTemplateController::class, 'show']);
        // POST (not PUT) so multipart preview uploads work reliably on all PHP stacks.
        Route::post('templates/{template}', [AdminTemplateController::class, 'update']);
        Route::delete('templates/{template}', [AdminTemplateController::class, 'destroy']);

        Route::get('subscription-plans', [AdminSubscriptionPlanController::class, 'index']);
        Route::post('subscription-plans', [AdminSubscriptionPlanController::class, 'store']);
        Route::get('subscription-plans/{subscriptionPlan}', [AdminSubscriptionPlanController::class, 'show']);
        Route::put('subscription-plans/{subscriptionPlan}', [AdminSubscriptionPlanController::class, 'update']);
        Route::delete('subscription-plans/{subscriptionPlan}', [AdminSubscriptionPlanController::class, 'destroy']);
    });

    // Images
    Route::delete('/projects/{project}/images/{image}', [ImageController::class, 'destroy']);

    // Templates (read-only for users)
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::get('/templates/{template}', [TemplateController::class, 'show']);

    Route::get('/credits/balance', [CreditsController::class, 'balance']);
    Route::get('/credits/transactions', [CreditsController::class, 'transactions']);
    Route::get('/credits/purchases', [CreditsController::class, 'purchases']);
    Route::post('/credits/purchase-stub/{slug}', [CreditsController::class, 'purchaseStub']);
    Route::get('/payments/checkout-context', [FastSpringController::class, 'checkoutContext']);
    Route::post('/payments/wayforpay/invoice', [WayForPayController::class, 'invoice']);
    Route::post('/payments/wayforpay/subscription-invoice', [WayForPayController::class, 'subscriptionInvoice']);
    Route::get('/payments/wayforpay/order-status', [WayForPayController::class, 'orderStatus']);
    Route::post('/payments/fastspring/session', [FastSpringController::class, 'session']);

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'update']);
        Route::post('/password', [ProfileController::class, 'changePassword']);
        Route::get('/stats', [ProfileController::class, 'stats']);
    });

    // AI Image Generation (Replicate)
    Route::post('/generate', [GenerationController::class, 'start']);
    Route::get('/generate/{predictionId}', [GenerationController::class, 'status']);
});
