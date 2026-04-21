<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description_en',
        'description_uk',
        'monthly_credits',
        'features',
        'price_cents',
        'currency',
        'stripe_price_id',
        'is_active',
        'is_featured',
        'display_variant',
        'sort_order',
        'subscription_tier',
    ];

    protected function casts(): array
    {
        return [
            'monthly_credits' => 'integer',
            'price_cents'     => 'integer',
            'is_active'       => 'boolean',
            'is_featured'     => 'boolean',
            'sort_order'      => 'integer',
            'subscription_tier' => 'integer',
            'features'        => 'array',
        ];
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }
}
