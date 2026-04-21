<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditPackageResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\CreditCost;
use App\Models\CreditPackage;
use App\Models\SubscriptionPlan;
use App\Services\Credits\CreditService;
use App\Services\Credits\PurchaseHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditsController extends Controller
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly PurchaseHistoryService $purchaseHistory,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('creditWallet');

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'balance' => $this->creditService->balance($user),
                'video_generation_cost' => $this->creditService->getOperationCost('video_generation'),
                'photo_guided_generation_cost' => $this->creditService->getOperationCost('photo_guided_generation'),
                'photo_flow' => $this->creditService->getPhotoFlowPricing(),
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min(max((int) $request->query('limit', 50), 1), 100);
        $rows = $this->creditService->recentTransactions($user, $limit);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $rows->map(fn ($t) => [
                'id' => $t->id,
                'delta' => $t->delta,
                'balance_after' => $t->balance_after,
                'kind' => $t->kind,
                'description' => $t->description,
                'created_at' => $t->created_at->toISOString(),
            ]),
        ]);
    }

    /**
     * Payment orders (packages + first subscription charge) plus WayForPay subscription renewals (credit ledger only).
     */
    public function purchases(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->purchaseHistory->paginatedForUserId((int) $user->id, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function packages(): JsonResponse
    {
        $packages = CreditPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => CreditPackageResource::collection($packages),
        ]);
    }

    public function subscriptionPlans(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    public function costs(): JsonResponse
    {
        $costs = CreditCost::query()->orderBy('operation_key')->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $costs->mapWithKeys(fn ($c) => [$c->operation_key => $c->cost]),
        ]);
    }

    /**
     * Stub purchase for local/dev until Stripe is wired. Guarded when not in debug.
     */
    public function purchaseStub(Request $request, string $slug): JsonResponse
    {
        if (! config('app.debug')) {
            abort(404);
        }

        $package = CreditPackage::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        try {
            $tx = $this->creditService->grantPackageCredits($request->user(), $package);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 422);
        }

        $request->user()->loadMissing('creditWallet');

        return response()->json([
            'success' => true,
            'message' => __('messages.credits.stub_purchase'),
            'data' => [
                'transaction_id' => $tx->id,
                'balance' => $this->creditService->balance($request->user()),
            ],
        ]);
    }
}
