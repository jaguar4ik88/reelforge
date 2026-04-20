<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditPackageResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\CreditCost;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\PaymentOrder;
use App\Models\SubscriptionPlan;
use App\Services\Credits\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditsController extends Controller
{
    public function __construct(private readonly CreditService $creditService) {}

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
        $limit = min(max((int) $request->query('limit', 50), 1), 100);

        $orders = PaymentOrder::query()
            ->where('user_id', $user->id)
            ->with([
                'creditPackage:id,slug,name,credits_amount',
                'subscriptionPlan:id,slug,name,monthly_credits',
            ])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $orderIdsWithCreditGrant = $orders->isEmpty()
            ? []
            : CreditTransaction::query()
                ->where('user_id', $user->id)
                ->where('reference_type', PaymentOrder::class)
                ->whereIn('reference_id', $orders->pluck('id'))
                ->pluck('reference_id')
                ->all();

        $creditedOrderIdSet = array_fill_keys($orderIdsWithCreditGrant, true);

        $orderRows = $orders->map(function (PaymentOrder $o) use ($creditedOrderIdSet) {
            $isSub = $o->subscription_plan_id !== null;
            $pkg = $o->creditPackage;
            $plan = $o->subscriptionPlan;
            $title = $isSub
                ? ($plan?->name ?? 'Subscription')
                : ($pkg?->name ?? 'Credit pack');
            $credits = $isSub
                ? (int) ($plan?->monthly_credits ?? 0)
                : (int) ($pkg?->credits_amount ?? 0);

            $hasCreditGrant = isset($creditedOrderIdSet[$o->id]);
            // If gateway callback never updated the row (e.g. local dev) but credits were granted, show completed.
            $displayStatus = $o->status === 'pending' && $hasCreditGrant
                ? 'completed'
                : $o->status;

            $awaitingCallback = $o->status === 'pending' && ! $hasCreditGrant;

            return [
                'source' => 'order',
                'id' => $o->id,
                'order_reference' => $o->order_reference,
                'status' => $displayStatus,
                'provider' => $o->provider,
                'kind' => $isSub ? 'subscription' : 'package',
                'title' => $title,
                'credits' => $credits,
                'amount_usd_cents' => $o->amount_usd_cents,
                'amount_uah' => $o->amount_uah !== null ? (string) $o->amount_uah : null,
                'created_at' => $o->created_at->toISOString(),
                'awaiting_payment_callback' => $awaitingCallback,
            ];
        });

        $renewals = CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('kind', 'subscription_wayforpay_renewal')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $planBySlug = SubscriptionPlan::query()
            ->whereIn(
                'slug',
                $renewals->pluck('meta.subscription_plan_slug')->filter()->unique()->values(),
            )
            ->get()
            ->keyBy('slug');

        $renewalRows = $renewals->map(function (CreditTransaction $t) use ($planBySlug) {
            $slug = isset($t->meta['subscription_plan_slug']) && is_string($t->meta['subscription_plan_slug'])
                ? $t->meta['subscription_plan_slug']
                : null;
            $plan = $slug !== null ? $planBySlug->get($slug) : null;
            $ref = isset($t->meta['wayforpay_order_reference']) && is_string($t->meta['wayforpay_order_reference'])
                ? $t->meta['wayforpay_order_reference']
                : null;

            return [
                'source' => 'renewal',
                'id' => $t->id,
                'order_reference' => $ref,
                'status' => 'completed',
                'provider' => 'wayforpay',
                'kind' => 'subscription_renewal',
                'title' => $plan?->name ?? 'Subscription renewal',
                'credits' => $t->delta,
                'amount_usd_cents' => null,
                'amount_uah' => null,
                'created_at' => $t->created_at->toISOString(),
                'awaiting_payment_callback' => false,
            ];
        });

        $merged = $orderRows->concat($renewalRows)
            ->sortByDesc(fn (array $row) => $row['created_at'])
            ->values()
            ->take($limit);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $merged,
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
