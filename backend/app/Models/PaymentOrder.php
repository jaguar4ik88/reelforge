<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    protected $fillable = [
        'order_reference',
        'user_id',
        'credit_package_id',
        'provider',
        'amount_uah',
        'amount_usd_cents',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount_uah' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class);
    }
}
