<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Profile\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new UserResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update($request->user(), $request->validated(), $request->file('avatar'));

        return response()->json([
            'success' => true,
            'message' => __('messages.profile.updated'),
            'data'    => new UserResource($user),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->profileService->changePassword(
            $request->user(),
            $request->current_password,
            $request->password,
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.profile.password_changed'),
            'data'    => [],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user  = $request->user();
        $stats = $this->profileService->getStats($user);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $stats,
        ]);
    }
}
