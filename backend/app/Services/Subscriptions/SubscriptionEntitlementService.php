<?php

namespace App\Services\Subscriptions;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Credits\CreditService;

class SubscriptionEntitlementService
{
    /**
     * Per-request memo (same service instance resolves UserResource fields multiple times).
     *
     * @var array<int, array{sub: ?UserSubscription, plan: ?SubscriptionPlan}>
     */
    private array $resolvedActiveByUserId = [];

    /**
     * @return array{sub: ?UserSubscription, plan: ?SubscriptionPlan}
     */
    private function resolveActive(User $user): array
    {
        $id = $user->id;
        if (! array_key_exists($id, $this->resolvedActiveByUserId)) {
            $sub = UserSubscription::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereNotNull('subscription_plan_id')
                ->with('subscriptionPlan')
                ->orderByDesc('current_period_end')
                ->first();
            $this->resolvedActiveByUserId[$id] = [
                'sub'  => $sub,
                'plan' => $sub?->subscriptionPlan,
            ];
        }

        return $this->resolvedActiveByUserId[$id];
    }

    public function activeSubscriptionPlan(User $user): ?SubscriptionPlan
    {
        return $this->resolveActive($user)['plan'];
    }

    /**
     * @return array{slug: string, name: string, monthly_credits: int, current_period_end: string|null, subscription_tier: int}|null
     */
    public function activeSubscriptionSummary(User $user): ?array
    {
        $row  = $this->resolveActive($user);
        $sub  = $row['sub'];
        $plan = $row['plan'];
        if ($sub === null || $plan === null) {
            return null;
        }

        return [
            'slug'                 => (string) $plan->slug,
            'name'                 => (string) $plan->name,
            'monthly_credits'      => (int) $plan->monthly_credits,
            'current_period_end'   => $sub->current_period_end?->toIso8601String(),
            'subscription_tier'    => max(1, min(4, (int) ($plan->subscription_tier ?? 1))),
        ];
    }

    /**
     * Product tier 1–4 (Starter … Business). No active subscription → tier 1 limits.
     */
    public function subscriptionTier(User $user): int
    {
        $plan = $this->activeSubscriptionPlan($user);
        if ($plan === null) {
            return 1;
        }

        $tier = (int) ($plan->subscription_tier ?? 1);

        return max(1, min(4, $tier));
    }

    /**
     * Max photo or video items generated in one request (batch quantity).
     */
    public function maxBatchQuantityPerGeneration(User $user): int
    {
        return match ($this->subscriptionTier($user)) {
            1, 2 => 1,
            3 => 2,
            4 => 3,
            default => 1,
        };
    }

    /**
     * Whether the user may use photo-flow content type "video" (tab + API).
     */
    public function photoGuidedVideoAllowed(User $user): bool
    {
        return $this->photoGuidedVideoRestrictionCode($user) === null;
    }

    /**
     * @return 'low_credits'|'no_subscription'|'starter_plan'|null null = allowed
     */
    public function photoGuidedVideoRestrictionCode(User $user): ?string
    {
        $min = (int) config('reelforge.credits.photo_guided_video.min_balance', 10);
        $balance = app(CreditService::class)->balance($user);
        if ($balance < $min) {
            return 'low_credits';
        }

        $plan = $this->activeSubscriptionPlan($user);
        if ($plan === null) {
            return 'no_subscription';
        }

        $blocked = config('reelforge.credits.photo_guided_video.blocked_plan_slugs', ['starter-monthly']);
        if (in_array($plan->slug, $blocked, true)) {
            return 'starter_plan';
        }

        return null;
    }
}
