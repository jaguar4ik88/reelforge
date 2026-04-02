<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Support\ReelForgeStorage;
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
}
