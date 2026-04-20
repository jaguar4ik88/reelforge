<?php

namespace App\Http\Resources;

use App\Services\Payments\WayForPayService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SubscriptionPlan */
class SubscriptionPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WayForPayService $wfp */
        $wfp = app(WayForPayService::class);
        $usdCents = (int) $this->price_cents;

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'monthly_credits' => (int) $this->monthly_credits,
            'price_cents' => $usdCents,
            'price_usd' => round($usdCents / 100, 2),
            'currency' => $this->currency,
            'amount_uah' => $wfp->enabled() ? $wfp->usdCentsToAmountUah($usdCents) : null,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
