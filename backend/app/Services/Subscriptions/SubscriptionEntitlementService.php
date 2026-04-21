<?php

namespace App\Services\Subscriptions;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Credits\CreditService;

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
