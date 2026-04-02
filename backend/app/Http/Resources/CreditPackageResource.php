<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'slug'           => $this->slug,
            'name'           => $this->name,
            'credits_amount' => $this->credits_amount,
            'price_cents'    => $this->price_cents,
            'currency'       => $this->currency,
            'sort_order'     => $this->sort_order,
        ];
    }
}
