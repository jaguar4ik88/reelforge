<?php

namespace App\Models;

use App\Services\Credits\CreditService;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use CanResetPassword, HasApiTokens, HasFactory, Notifiable;

    public const ROLE_CLIENT = 'client';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'plan',
        'role',
        'locale',
        'avatar_path',
        'subscription_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function creditWallet(): HasOne
    {
        return $this->hasOne(UserCredit::class);
    }

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function videosThisMonth(): int
    {
        return $this->projects()
            ->where('status', 'done')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function videoLimit(): int
    {
        return match ($this->plan) {
            'pro' => (int) config('platform.pro_plan_videos_per_month', 100),
            default => (int) config('platform.free_plan_videos_per_month', 10),
        };
    }

    public function isClient(): bool
    {
        return ($this->role ?? self::ROLE_CLIENT) === self::ROLE_CLIENT;
    }

    public function isManager(): bool
    {
        return ($this->role ?? self::ROLE_CLIENT) === self::ROLE_MANAGER;
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? self::ROLE_CLIENT) === self::ROLE_ADMIN;
    }

    /** Admin or manager — staff area at /admin. */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isManager();
    }

    public function canGenerateVideo(): bool
    {
        $creditService = app(CreditService::class);
        $cost = $creditService->getOperationCost('video_generation');

        if (config('platform.credits.require_for_generation', true)) {
            if (! $creditService->canSpend($this, $cost)) {
                return false;
            }
        } else {
            if ($this->videosThisMonth() >= $this->videoLimit()) {
                return false;
            }
        }

        if (config('platform.credits.enforce_monthly_cap', false)) {
            return $this->videosThisMonth() < $this->videoLimit();
        }

        return true;
    }
}
