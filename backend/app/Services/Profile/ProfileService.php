<?php

namespace App\Services\Profile;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\Subscriptions\SubscriptionEntitlementService;
use App\Support\ReelForgeStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function update(User $user, array $data, ?UploadedFile $avatar): User
    {
        if ($avatar) {
            if ($user->avatar_path) {
                Storage::disk(ReelForgeStorage::contentDisk())->delete($user->avatar_path);
            }
            $disk                = ReelForgeStorage::contentDisk();
            $data['avatar_path'] = $avatar->store(ReelForgeStorage::avatarsPath($user->id), $disk);
        }

        $user->update($data);

        return $user->fresh();
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if ($user->password === null) {
            throw ValidationException::withMessages([
                'current_password' => [__('messages.profile.password_oauth_only')],
            ]);
        }

        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('messages.profile.wrong_password')],
            ]);
        }

        $user->update(['password' => $newPassword]);
    }

    public function getStats(User $user): array
    {
        $projects = $user->projects();
        $user->loadMissing('creditWallet');

        return [
            'total_projects'      => $projects->count(),
            'done_projects'       => $projects->where('status', 'done')->count(),
            'failed_projects'     => $projects->where('status', 'failed')->count(),
            'processing_projects' => $projects->where('status', 'processing')->count(),
            'videos_this_month'   => $user->videosThisMonth(),
            'video_limit'         => $user->videoLimit(),
            'credits_balance'     => (int) ($user->creditWallet?->balance ?? 0),
            'plan'                => $user->plan,
            'member_since'        => $user->created_at->toISOString(),
        ];
    }

    /**
     * Landing / home summary: plan, credits, output counts, last 4 projects.
     */
    public function getHomeDashboard(User $user): array
    {
        $user->loadMissing('creditWallet');

        $recent = Project::query()
            ->where('user_id', $user->id)
            ->with(['template', 'images', 'latestGenerationJob'])
            ->latest()
            ->limit(4)
            ->get();

        $request = request() instanceof Request ? request() : Request::create('/');
        /** @var SubscriptionEntitlementService $subs */
        $subs = app(SubscriptionEntitlementService::class);

        return [
            'plan'               => (string) ($user->plan ?? 'free'),
            'subscription'       => $subs->activeSubscriptionSummary($user),
            'credits_balance'    => (int) ($user->creditWallet?->balance ?? 0),
            'images_generated'   => $this->countCompletedImageOutputs($user->id),
            'videos_generated'   => $this->countCompletedVideoOutputs($user->id),
            'recent_projects'    => $recent->map(fn (Project $p) => (new ProjectResource($p))->toArray($request))->values()->all(),
        ];
    }

    /** Done projects whose primary file is a raster image (photo-guided image/card, etc.). */
    private function countCompletedImageOutputs(int $userId): int
    {
        return Project::query()
            ->where('user_id', $userId)
            ->where('status', 'done')
            ->whereNotNull('video_path')
            ->where(function ($q) {
                foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                    $q->orWhereRaw('LOWER(video_path) LIKE ?', ['%.'.$ext]);
                }
            })
            ->count();
    }

    /** Done projects whose primary file is MP4 (template videos or photo-guided video). */
    private function countCompletedVideoOutputs(int $userId): int
    {
        return Project::query()
            ->where('user_id', $userId)
            ->where('status', 'done')
            ->whereNotNull('video_path')
            ->whereRaw('LOWER(video_path) LIKE ?', ['%.mp4'])
            ->count();
    }
}
