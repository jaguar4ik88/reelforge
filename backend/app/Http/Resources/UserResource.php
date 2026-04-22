<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\Credits\CreditService;
use App\Services\Subscriptions\SubscriptionEntitlementService;
use App\Support\ReelForgeStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('creditWallet');
        $creditService = app(CreditService::class);
        /** @var SubscriptionEntitlementService $entitlements */
        $entitlements = app(SubscriptionEntitlementService::class);
        $photoGuidedVideoCode = $entitlements->photoGuidedVideoRestrictionCode($this->resource);
        $subscriptionSummary = $entitlements->activeSubscriptionSummary($this->resource);

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'plan'              => $this->plan,
            'role'              => $this->role ?? User::ROLE_CLIENT,
            'locale'            => $this->locale ?? 'uk',
            'avatar_url'        => ReelForgeStorage::url(
                ReelForgeStorage::contentDisk(),
                $this->avatar_path,
                2
            ),
            'credits'           => [
                'balance'                    => (int) ($this->creditWallet?->balance ?? 0),
                'video_generation_cost'      => $creditService->getOperationCost('video_generation'),
                'photo_guided_generation_cost' => $creditService->getOperationCost('photo_guided_generation'),
                'photo_flow'                 => $creditService->getPhotoFlowPricing(),
            ],
            'videos_this_month' => $this->videosThisMonth(),
            'video_limit'       => $this->videoLimit(),
            'can_generate'      => $this->canGenerateVideo(),
            'generation_limits' => [
                'subscription_tier'    => $entitlements->subscriptionTier($this->resource),
                'max_batch_quantity'   => $entitlements->maxBatchQuantityPerGeneration($this->resource),
            ],
            'subscription'            => $subscriptionSummary,
            'has_active_subscription' => $subscriptionSummary !== null,
            'photo_guided_video' => [
                'allowed'     => $photoGuidedVideoCode === null,
                'min_balance' => (int) config('platform.credits.photo_guided_video.min_balance', 10),
                'code'        => $photoGuidedVideoCode,
            ],
            'created_at'        => $this->created_at->toISOString(),
        ];
    }
}
