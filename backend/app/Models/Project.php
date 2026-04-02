<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'creation_flow',
        'title',
        'price',
        'description',
        'status',
        'template_id',
        'video_path',
        'credits_cost',
        'credits_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('order');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function creditsTransaction(): BelongsTo
    {
        return $this->belongsTo(CreditTransaction::class, 'credits_transaction_id');
    }

    public function generationJobs(): HasMany
    {
        return $this->hasMany(GenerationJob::class);
    }

    public function isPhotoGuided(): bool
    {
        return $this->creation_flow === 'photo_guided';
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
