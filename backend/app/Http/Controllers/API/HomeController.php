<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Profile\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $this->profileService->getHomeDashboard($request->user()),
        ]);
    }
}
