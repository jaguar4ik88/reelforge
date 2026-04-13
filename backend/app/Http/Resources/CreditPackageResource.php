<?php

namespace App\Http\Resources;

use App\Services\Payments\WayForPayService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var WayForPayService $wfp */
        $wfp = app(WayForPayService::class);
        $cents = (int) $this->price_cents;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'credits_amount' => $this->credits_amount,
            'price_cents' => $this->price_cents,
            'price_usd' => round($cents / 100, 2),
            'currency' => $this->currency,
            'sort_order' => $this->sort_order,
            'amount_uah' => $wfp->enabled() ? $wfp->usdCentsToAmountUah($cents) : null,
        ];
    }
}
