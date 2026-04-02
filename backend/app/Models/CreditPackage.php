<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPackage extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'credits_amount',
        'price_cents',
        'currency',
        'stripe_price_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'credits_amount' => 'integer',
            'price_cents'    => 'integer',
            'is_active'      => 'boolean',
            'sort_order'     => 'integer',
        ];
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }
}
