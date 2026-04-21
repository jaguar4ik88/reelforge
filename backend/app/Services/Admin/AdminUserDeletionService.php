<?php

namespace App\Services\Admin;

use App\Models\GenerationJob;
use App\Models\Project;
use App\Models\User;
use App\Services\Project\ProjectService;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminUserDeletionService
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    /**
     * Hard-delete user data: projects (storage + DB), jobs, Sanctum tokens, wallet & ledger, payments, subscriptions.
     */
    public function deleteUserCompletely(User $target, User $actingAdmin): void
    {
        abort_if($target->id === $actingAdmin->id, 422, __('messages.admin.user_cannot_delete_self'));
        abort_if($target->isAdmin(), 422, __('messages.admin.user_cannot_delete_admin'));

        DB::transaction(function () use ($target): void {
            $projectIds = Project::query()
                ->where('user_id', $target->id)
                ->pluck('id')
                ->all();

            $jobs = GenerationJob::query()
                ->where(function ($q) use ($target, $projectIds): void {
                    $q->where('user_id', $target->id);
                    if ($projectIds !== []) {
                        $q->orWhereIn('project_id', $projectIds);
                    }
                })
                ->get();

            $disk = ReelForgeStorage::contentDisk();
            foreach ($jobs as $job) {
                if (filled($job->result_path)) {
                    Storage::disk($disk)->delete($job->result_path);
                }
            }

            $projects = Project::query()
                ->where('user_id', $target->id)
                ->with('images')
                ->get();

            foreach ($projects as $project) {
                $this->projectService->delete($project);
            }

            GenerationJob::query()->where('user_id', $target->id)->delete();

            $target->tokens()->delete();

            DB::table('sessions')->where('user_id', $target->id)->delete();
            DB::table('password_reset_tokens')->where('email', $target->email)->delete();

            if (filled($target->avatar_path)) {
                Storage::disk($disk)->delete($target->avatar_path);
            }

            $target->delete();
        });
    }
}
