<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSubscriptionPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    public function show(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data' => new SubscriptionPlanResource($subscriptionPlan),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $plan = SubscriptionPlan::query()->create($data);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => new SubscriptionPlanResource($plan),
        ], 201);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $data = $this->validatedData($request, $subscriptionPlan->id);

        $subscriptionPlan->update($data);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => new SubscriptionPlanResource($subscriptionPlan->fresh()),
        ]);
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $hasSubscriptions = UserSubscription::query()
            ->where('subscription_plan_id', $subscriptionPlan->id)
            ->exists();

        if ($hasSubscriptions) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a plan that has active or past subscriptions. Deactivate it instead.',
                'errors' => [],
            ], 422);
        }

        $subscriptionPlan->delete();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = Rule::unique('subscription_plans', 'slug');
        if ($ignoreId !== null) {
            $slugRule = $slugRule->ignore($ignoreId);
        }

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'name' => ['required', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_uk' => ['nullable', 'string', 'max:10000'],
            'monthly_credits' => ['required', 'integer', 'min:1', 'max:999999'],
            'features' => ['nullable', 'array'],
            'features.*.key' => ['required_with:features', 'string', 'max:128'],
            'features.*.params' => ['nullable', 'array'],
            'price_cents' => ['required', 'integer', 'min:1', 'max:999999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'display_variant' => [
                'nullable',
                'string',
                'max:32',
                Rule::in(['starter', 'creator', 'pro', 'studio', 'business']),
            ],
            'sort_order' => ['integer', 'min:0', 'max:999999'],
            'subscription_tier' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        $validated['currency'] = strtoupper((string) ($validated['currency'] ?? 'USD'));
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $validated['subscription_tier'] = (int) ($validated['subscription_tier'] ?? 1);
        $validated['features'] = isset($validated['features']) && is_array($validated['features'])
            ? array_values($validated['features'])
            : [];

        return $validated;
    }
}
