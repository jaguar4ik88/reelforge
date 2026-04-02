<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationJob extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'kind',
        'status',
        'settings_json',
        'image_caption',
        'final_prompt',
        'provider',
        'credits_cost',
        'credits_transaction_id',
        'result_path',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'credits_cost'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creditsTransaction(): BelongsTo
    {
        return $this->belongsTo(CreditTransaction::class, 'credits_transaction_id');
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }
}
