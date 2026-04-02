<?php

namespace App\Services\Credits;

use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditCost;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\GenerationJob;
use App\Models\Project;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /** @var array<string, int> */
    private array $costCache = [];

    public function getOrCreateWallet(User $user): UserCredit
    {
        return UserCredit::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
    }

    public function balance(User $user): int
    {
        return (int) ($user->creditWallet?->balance ?? $this->getOrCreateWallet($user)->balance);
    }

    public function getOperationCost(string $operationKey): int
    {
        if (! array_key_exists($operationKey, $this->costCache)) {
            $fromDb = CreditCost::query()
                ->where('operation_key', $operationKey)
                ->value('cost');

            $this->costCache[$operationKey] = $fromDb !== null
                ? (int) $fromDb
                : (int) match ($operationKey) {
                    'photo_guided_generation' => config('reelforge.credits.default_photo_guided_cost', 5),
                    default => config('reelforge.credits.default_video_cost', 10),
                };
        }

        return $this->costCache[$operationKey];
    }

    public function canSpend(User $user, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        return $this->balance($user) >= $amount;
    }

    public function spendForVideoGeneration(User $user, Project $project, int $cost): CreditTransaction
    {
        return DB::transaction(function () use ($user, $project, $cost) {
            $this->getOrCreateWallet($user);

            $wallet = UserCredit::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $cost) {
                throw new InsufficientCreditsException('Insufficient credits.');
            }

            $wallet->decrement('balance', $cost);
            $balanceAfter = (int) $wallet->fresh()->balance;

            return CreditTransaction::query()->create([
                'user_id'        => $user->id,
                'delta'          => -$cost,
                'balance_after'  => $balanceAfter,
                'kind'           => 'spend_generation',
                'description'    => 'Video generation',
                'reference_type' => Project::class,
                'reference_id'   => $project->id,
            ]);
        });
    }

    public function spendForPhotoGuidedGeneration(User $user, GenerationJob $job, int $cost): CreditTransaction
    {
        return DB::transaction(function () use ($user, $job, $cost) {
            $this->getOrCreateWallet($user);

            $wallet = UserCredit::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $cost) {
                throw new InsufficientCreditsException('Insufficient credits.');
            }

            $wallet->decrement('balance', $cost);
            $balanceAfter = (int) $wallet->fresh()->balance;

            return CreditTransaction::query()->create([
                'user_id'          => $user->id,
                'delta'            => -$cost,
                'balance_after'    => $balanceAfter,
                'kind'             => 'spend_photo_guided',
                'description'      => 'Photo-guided generation',
                'reference_type'   => GenerationJob::class,
                'reference_id'     => $job->id,
            ]);
        });
    }

    public function refundFailedPhotoGuidedGeneration(GenerationJob $job): void
    {
        if ($job->credits_transaction_id === null || $job->credits_cost === null) {
            return;
        }

        $amount = (int) $job->credits_cost;
        $userId = (int) $job->user_id;

        DB::transaction(function () use ($job, $amount, $userId) {
            $locked = GenerationJob::query()->whereKey($job->id)->lockForUpdate()->first();
            if ($locked === null || $locked->credits_transaction_id === null) {
                return;
            }

            $this->getOrCreateWallet(User::query()->findOrFail($userId));

            $wallet = UserCredit::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('balance', $amount);
            $balanceAfter = (int) $wallet->fresh()->balance;

            CreditTransaction::query()->create([
                'user_id'         => $userId,
                'delta'           => $amount,
                'balance_after'   => $balanceAfter,
                'kind'            => 'refund_photo_guided',
                'description'     => 'Photo-guided generation failed — credits refunded',
                'reference_type'  => GenerationJob::class,
                'reference_id'    => $job->id,
            ]);

            $locked->forceFill([
                'credits_transaction_id' => null,
                'credits_cost'             => null,
            ])->save();
        });
    }

    public function refundFailedVideoGeneration(Project $project): void
    {
        if ($project->credits_transaction_id === null || $project->credits_cost === null) {
            return;
        }

        $amount = (int) $project->credits_cost;
        $userId = (int) $project->user_id;

        DB::transaction(function () use ($project, $amount, $userId) {
            $locked = Project::query()->whereKey($project->id)->lockForUpdate()->first();
            if ($locked === null || $locked->credits_transaction_id === null) {
                return;
            }

            $this->getOrCreateWallet(User::query()->findOrFail($userId));

            $wallet = UserCredit::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('balance', $amount);
            $balanceAfter = (int) $wallet->fresh()->balance;

            CreditTransaction::query()->create([
                'user_id'        => $userId,
                'delta'          => $amount,
                'balance_after'  => $balanceAfter,
                'kind'           => 'refund_generation',
                'description'    => 'Video generation failed — credits refunded',
                'reference_type' => Project::class,
                'reference_id'   => $project->id,
            ]);

            $locked->forceFill([
                'credits_transaction_id' => null,
                'credits_cost'           => null,
            ])->save();
        });
    }

    /**
     * @return Collection<int, CreditTransaction>
     */
    public function recentTransactions(User $user, int $limit = 50): Collection
    {
        return CreditTransaction::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Dev / stub: grant credits from a package without Stripe.
     */
    public function grantPackageCredits(User $user, CreditPackage $package): CreditTransaction
    {
        return DB::transaction(function () use ($user, $package) {
            $this->getOrCreateWallet($user);

            $wallet = UserCredit::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (int) $package->credits_amount;
            $wallet->increment('balance', $amount);
            $balanceAfter = (int) $wallet->fresh()->balance;

            return CreditTransaction::query()->create([
                'user_id'           => $user->id,
                'delta'             => $amount,
                'balance_after'     => $balanceAfter,
                'kind'              => 'purchase_stub',
                'description'       => 'Credit package (stub)',
                'credit_package_id' => $package->id,
            ]);
        });
    }
}
