<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\Project;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function overview(): JsonResponse
    {
        $totalCredits = (int) UserCredit::query()->sum('balance');

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'users_count' => User::query()->count(),
                'total_credits_balance' => $totalCredits,
                'projects_count' => Project::query()->count(),
                'payment_orders_count' => PaymentOrder::query()->count(),
                'payment_orders_completed' => PaymentOrder::query()->where('status', 'completed')->count(),
                'users_by_role' => User::query()
                    ->select('role', DB::raw('count(*) as c'))
                    ->groupBy('role')
                    ->pluck('c', 'role')
                    ->all(),
            ],
        ]);
    }
}
