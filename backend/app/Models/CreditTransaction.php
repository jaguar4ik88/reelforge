<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CreditTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'delta',
        'balance_after',
        'kind',
        'description',
        'reference_type',
        'reference_id',
        'credit_package_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'delta'          => 'integer',
            'balance_after'  => 'integer',
            'meta'           => 'array',
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

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
