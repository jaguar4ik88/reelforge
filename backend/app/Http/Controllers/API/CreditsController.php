<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditPackageResource;
use App\Models\CreditCost;
use App\Models\CreditPackage;
use App\Services\Credits\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditsController extends Controller
{
    public function __construct(private readonly CreditService $creditService) {}

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('creditWallet');

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'balance'                      => $this->creditService->balance($user),
                'video_generation_cost'        => $this->creditService->getOperationCost('video_generation'),
                'photo_guided_generation_cost' => $this->creditService->getOperationCost('photo_guided_generation'),
                'photo_flow'                   => $this->creditService->getPhotoFlowPricing(),
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min(max((int) $request->query('limit', 50), 1), 100);
        $rows = $this->creditService->recentTransactions($user, $limit);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $rows->map(fn ($t) => [
                'id'             => $t->id,
                'delta'          => $t->delta,
                'balance_after'  => $t->balance_after,
                'kind'           => $t->kind,
                'description'    => $t->description,
                'created_at'     => $t->created_at->toISOString(),
            ]),
        ]);
    }

    public function packages(): JsonResponse
    {
        $packages = CreditPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => CreditPackageResource::collection($packages),
        ]);
    }

    public function costs(): JsonResponse
    {
        $costs = CreditCost::query()->orderBy('operation_key')->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $costs->mapWithKeys(fn ($c) => [$c->operation_key => $c->cost]),
        ]);
    }

    /**
     * Stub purchase for local/dev until Stripe is wired. Guarded when not in debug.
     */
    public function purchaseStub(Request $request, string $slug): JsonResponse
    {
        if (! config('app.debug')) {
            abort(404);
        }

        $package = CreditPackage::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        try {
            $tx = $this->creditService->grantPackageCredits($request->user(), $package);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => [],
            ], 422);
        }

        $request->user()->loadMissing('creditWallet');

        return response()->json([
            'success' => true,
            'message' => __('messages.credits.stub_purchase'),
            'data'    => [
                'transaction_id' => $tx->id,
                'balance'        => $this->creditService->balance($request->user()),
            ],
        ]);
    }
}
