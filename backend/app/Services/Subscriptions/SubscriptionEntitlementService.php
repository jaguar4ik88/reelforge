<?php

namespace App\Services\Subscriptions;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;

class SubscriptionEntitlementService
{
    public function activeSubscriptionPlan(User $user): ?SubscriptionPlan
    {
        $sub = UserSubscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('subscription_plan_id')
            ->with('subscriptionPlan')
            ->orderByDesc('current_period_end')
            ->first();

        return $sub?->subscriptionPlan;
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
}
