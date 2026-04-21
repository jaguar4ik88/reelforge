<?php

namespace App\Services\Credits;

use App\Models\CreditTransaction;
use App\Models\PaymentOrder;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Collection;

class PurchaseHistoryService
{
    /**
     * Payment orders plus WayForPay subscription renewals (ledger), merged by date, paginated.
     *
     * @return array{data: Collection<int, array<string, mixed>>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function paginatedForUserId(int $userId, int $page, int $perPage): array
    {
        $orders = PaymentOrder::query()
            ->where('user_id', $userId)
            ->with([
                'creditPackage:id,slug,name,credits_amount',
                'subscriptionPlan:id,slug,name,monthly_credits',
            ])
            ->orderByDesc('created_at')
            ->get();

        $orderIdsWithCreditGrant = $orders->isEmpty()
            ? []
            : CreditTransaction::query()
                ->where('user_id', $userId)
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
            ->where('user_id', $userId)
            ->where('kind', 'subscription_wayforpay_renewal')
            ->orderByDesc('created_at')
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
            ->values();

        $total = $merged->count();
        $lastPage = $perPage > 0 ? (int) max(1, (int) ceil($total / $perPage)) : 1;
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $offset = ($page - 1) * $perPage;
        $pageItems = $merged->slice($offset, $perPage)->values();

        return [
            'data' => $pageItems,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }
}
