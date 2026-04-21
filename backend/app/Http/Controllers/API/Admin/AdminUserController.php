<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminUserDeletionService;
use App\Services\Credits\CreditService;
use App\Services\Credits\PurchaseHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly PurchaseHistoryService $purchaseHistory,
        private readonly AdminUserDeletionService $userDeletion,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $q = trim((string) $request->query('q', ''));

        $query = User::query()
            ->with('creditWallet:id,user_id,balance')
            ->when($q !== '', function ($qq) use ($q): void {
                $qq->where(function ($w) use ($q): void {
                    $w->where('email', 'like', '%'.$q.'%')
                        ->orWhere('name', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role ?? 'client',
            'plan' => $u->plan ?? 'free',
            'credits_balance' => (int) ($u->creditWallet?->balance ?? 0),
            'created_at' => $u->created_at?->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadMissing('creditWallet');

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'client',
                'plan' => $user->plan ?? 'free',
                'locale' => $user->locale,
                'credits_balance' => $this->creditService->balance($user),
                'created_at' => $user->created_at?->toISOString(),
            ],
        ]);
    }

    public function updateCredits(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'balance' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);

        try {
            $tx = $this->creditService->adminSetBalance(
                $user,
                (int) $validated['balance'],
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['balance' => [$e->getMessage()]],
            ], 422);
        }

        $user->loadMissing('creditWallet');

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'balance' => $this->creditService->balance($user),
                'transaction_id' => $tx?->id,
            ],
        ]);
    }

    public function purchases(Request $request, User $user): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->purchaseHistory->paginatedForUserId((int) $user->id, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->userDeletion->deleteUserCompletely($user, $request->user());

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_deleted'),
            'data' => null,
        ]);
    }
}
