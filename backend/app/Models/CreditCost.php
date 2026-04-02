<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCost extends Model
{
    protected $fillable = [
        'operation_key',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
        ];
    }
}
