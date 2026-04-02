<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'monthly_credits',
        'price_cents',
        'currency',
        'stripe_price_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_credits' => 'integer',
            'price_cents'     => 'integer',
            'is_active'       => 'boolean',
            'sort_order'      => 'integer',
        ];
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }
}
