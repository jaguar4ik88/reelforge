<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\Credits\CreditService;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\WayForPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WayForPayController extends Controller
{
    public function __construct(
        private readonly WayForPayService $wayForPay,
        private readonly CreditService $creditService,
        private readonly PaymentGatewayResolver $gatewayResolver,
    ) {}

    /**
     * Authenticated: start WayForPay purchase (form POST to secure.wayforpay.com).
     */
    public function invoice(Request $request): JsonResponse
    {
        if (! $this->wayForPay->enabled()) {
            abort(503, 'WayForPay is not configured.');
        }

        if ($this->gatewayResolver->resolveBillingProvider($request) !== 'wayforpay') {
            abort(422, 'WayForPay is not available for this region.');
        }

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:64'],
        ]);

        $package = CreditPackage::query()
            ->where('slug', $validated['slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();
        $usdCents = (int) $package->price_cents;
        $amountUah = $this->wayForPay->usdCentsToAmountUah($usdCents);

        $orderReference = 'rfw_'.Str::lower(Str::random(32));

        $order = PaymentOrder::query()->create([
            'order_reference' => $orderReference,
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'subscription_plan_id' => null,
            'provider' => 'wayforpay',
            'amount_uah' => $amountUah,
            'amount_usd_cents' => $usdCents,
            'status' => 'pending',
            'meta' => [],
        ]);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $this->buildWayForPayPurchaseResponse(
                $orderReference,
                $order->id,
                $amountUah,
                $usdCents,
                $this->wayForPay->buildPurchaseFormFields(
                    $orderReference,
                    $package,
                    $amountUah,
                    $user->email,
                    $this->returnUrl(),
                    $this->serviceUrl(),
                ),
            ),
        ]);
    }

    /**
     * Authenticated: monthly subscription (regularMode monthly) via same /pay endpoint.
     */
    public function subscriptionInvoice(Request $request): JsonResponse
    {
        if (! $this->wayForPay->enabled()) {
            abort(503, 'WayForPay is not configured.');
        }

        if ($this->gatewayResolver->resolveBillingProvider($request) !== 'wayforpay') {
            abort(422, 'WayForPay is not available for this region.');
        }

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:64'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('slug', $validated['slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();
        $usdCents = (int) $plan->price_cents;
        $amountUah = $this->wayForPay->usdCentsToAmountUah($usdCents);

        $orderReference = 'rfs_'.Str::lower(Str::random(32));

        $order = PaymentOrder::query()->create([
            'order_reference' => $orderReference,
            'user_id' => $user->id,
            'credit_package_id' => null,
            'subscription_plan_id' => $plan->id,
            'provider' => 'wayforpay',
            'amount_uah' => $amountUah,
            'amount_usd_cents' => $usdCents,
            'status' => 'pending',
            'meta' => ['subscription_slug' => $plan->slug],
        ]);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $this->buildWayForPayPurchaseResponse(
                $orderReference,
                $order->id,
                $amountUah,
                $usdCents,
                $this->wayForPay->buildSubscriptionPurchaseFormFields(
                    $orderReference,
                    $plan,
                    $amountUah,
                    $user->email,
                    $this->returnUrl(),
                    $this->serviceUrl(),
                    (string) $user->id,
                ),
            ),
        ]);
    }

    /**
     * Authenticated: poll order status after browser return from WayForPay.
     * Credits are granted in {@see callback()} (serviceUrl), not on returnUrl.
     */
    public function orderStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_reference' => ['required', 'string', 'max:64'],
        ]);

        $order = PaymentOrder::query()
            ->where('order_reference', $validated['order_reference'])
            ->where('user_id', $request->user()->id)
            ->where('provider', 'wayforpay')
            ->first();

        if ($order === null) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'status' => $order->status,
                'order_reference' => $order->order_reference,
            ],
        ]);
    }

    /**
     * WayForPay server-to-server callback (no auth; signature verified).
     */
    public function callback(Request $request): Response|JsonResponse
    {
        if (! $this->wayForPay->isConfigured()) {
            return response()->json(['message' => 'Not configured'], 503);
        }

        $data = $request->json()->all();
        if ($data === []) {
            $data = $request->all();
        }

        if (! $this->wayForPay->verifyServiceCallback($data)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $orderReference = (string) ($data['orderReference'] ?? '');
        $status = (string) ($data['transactionStatus'] ?? '');

        $order = PaymentOrder::query()->where('order_reference', $orderReference)->first();

        if ($order !== null) {
            if ($order->provider !== 'wayforpay') {
                return response()->json(['message' => 'Invalid order provider'], 422);
            }

            $amountStr = isset($data['amount']) ? $this->normalizeAmountString($data['amount']) : null;
            if ($amountStr !== null && ! $this->amountsMatch($amountStr, (string) $order->amount_uah)) {
                Log::warning('WayForPay callback amount mismatch', [
                    'order_reference' => $orderReference,
                    'expected' => (string) $order->amount_uah,
                    'got' => $amountStr,
                ]);

                return response()->json(['message' => 'Amount mismatch'], 422);
            }

            if ($order->subscription_plan_id !== null) {
                return $this->handleSubscriptionOrderCallback($order, $data, $status);
            }

            return $this->handleCreditPackageOrderCallback($order, $data, $status);
        }

        return $this->handleSubscriptionRenewalCallback($data, $status);
    }

    /**
     * @param  array<string, string|array<int, string>>  $fields
     * @return array<string, mixed>
     */
    private function buildWayForPayPurchaseResponse(
        string $orderReference,
        int $paymentOrderId,
        string $amountUah,
        int $amountUsdCents,
        array $fields,
    ): array {
        $fieldPairs = $this->wayForPay->flattenFormFieldsForHtml($fields);

        return [
            'pay_url' => $this->wayForPay->payUrl(),
            'order_reference' => $orderReference,
            'payment_order_id' => $paymentOrderId,
            'amount_uah' => $amountUah,
            'amount_usd_cents' => $amountUsdCents,
            'fields' => $fieldPairs,
        ];
    }

    private function returnUrl(): string
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        $path = '/app/credits?payment=return';

        return $frontend !== '' ? $frontend.$path : url($path);
    }

    private function serviceUrl(): string
    {
        $appUrl = rtrim((string) config('app.url', ''), '/');

        return $appUrl.'/api/payments/wayforpay/callback';
    }

    /**
     * Successful charge — only this status grants credits for Purchase.
     *
     * @see https://wiki.wayforpay.com/en/view/852131
     */
    private function isWayForPayApproved(string $status): bool
    {
        return strcasecmp(trim($status), 'Approved') === 0;
    }

    /**
     * Terminal failure only. Do NOT treat inProcessing / Pending / WaitingAuthComplete as failed —
     * WayForPay may send those before the final Approved callback.
     *
     * @see https://wiki.wayforpay.com/en/view/852131
     */
    private function isWayForPayTerminalFailure(string $status): bool
    {
        $s = trim($status);
        if ($s === '') {
            return false;
        }

        return match (true) {
            strcasecmp($s, 'Declined') === 0,
            strcasecmp($s, 'Expired') === 0,
            strcasecmp($s, 'Refunded/Voided') === 0 => true,
            default => false,
        };
    }

    private function handleCreditPackageOrderCallback(PaymentOrder $order, array $data, string $status): Response|JsonResponse
    {
        if ($this->isWayForPayApproved($status)) {
            $this->creditService->grantCreditsForWayForPayOrder($order);
        } elseif ($order->status === 'pending' && $this->isWayForPayTerminalFailure($status)) {
            $order->forceFill([
                'status' => 'failed',
                'meta' => array_merge($order->meta ?? [], ['wayforpay' => $data]),
            ])->save();
        }

        $response = $this->wayForPay->buildServiceAcceptResponse($order->order_reference);

        return response()->json($response);
    }

    private function handleSubscriptionOrderCallback(PaymentOrder $order, array $data, string $status): Response|JsonResponse
    {
        if ($this->isWayForPayApproved($status)) {
            $this->creditService->grantCreditsForWayForPaySubscriptionOrder($order);
            $this->creditService->attachWayForPaySubscriptionAfterPayment($order, $data);
            $order->refresh();
            $order->forceFill([
                'meta' => array_merge($order->meta ?? [], ['wayforpay' => $data]),
            ])->save();
        } elseif ($order->status === 'pending' && $this->isWayForPayTerminalFailure($status)) {
            $order->forceFill([
                'status' => 'failed',
                'meta' => array_merge($order->meta ?? [], ['wayforpay' => $data]),
            ])->save();
        }

        $response = $this->wayForPay->buildServiceAcceptResponse($order->order_reference);

        return response()->json($response);
    }

    private function handleSubscriptionRenewalCallback(array $data, string $status): Response|JsonResponse
    {
        $recToken = $data['recToken'] ?? null;
        if (! is_string($recToken) || $recToken === '' || ! $this->isWayForPayApproved($status)) {
            $ref = (string) ($data['orderReference'] ?? '');

            return response()->json($this->wayForPay->buildServiceAcceptResponse($ref !== '' ? $ref : 'unknown'));
        }

        $sub = UserSubscription::query()
            ->where('rec_token', $recToken)
            ->where('status', 'active')
            ->with(['subscriptionPlan', 'user'])
            ->first();

        $ref = (string) ($data['orderReference'] ?? '');

        if ($sub === null || $sub->subscriptionPlan === null) {
            Log::warning('WayForPay renewal: no active subscription for recToken', [
                'order_reference' => $data['orderReference'] ?? null,
            ]);

            return response()->json($this->wayForPay->buildServiceAcceptResponse($ref !== '' ? $ref : 'unknown'));
        }

        $plan = $sub->subscriptionPlan;
        $expectedUah = $this->wayForPay->usdCentsToAmountUah((int) $plan->price_cents);
        $amountStr = isset($data['amount']) ? $this->normalizeAmountString($data['amount']) : null;
        if ($amountStr !== null && ! $this->amountsMatch($amountStr, $expectedUah)) {
            Log::warning('WayForPay renewal amount mismatch', [
                'order_reference' => $data['orderReference'] ?? null,
                'expected' => $expectedUah,
                'got' => $amountStr,
            ]);

            return response()->json($this->wayForPay->buildServiceAcceptResponse($ref !== '' ? $ref : 'unknown'));
        }

        $user = $sub->user;
        if ($user === null) {
            return response()->json($this->wayForPay->buildServiceAcceptResponse($ref !== '' ? $ref : 'unknown'));
        }

        $this->creditService->grantCreditsForWayForPaySubscriptionRenewal(
            $user,
            $plan,
            $ref,
            $data
        );

        $sub->forceFill([
            'current_period_end' => now()->addMonth(),
        ])->save();

        return response()->json($this->wayForPay->buildServiceAcceptResponse($ref !== '' ? $ref : 'unknown'));
    }

    private function normalizeAmountString(mixed $amount): string
    {
        if (is_numeric($amount)) {
            return number_format((float) $amount, 2, '.', '');
        }

        return (string) $amount;
    }

    private function amountsMatch(string $a, string $b): bool
    {
        return abs((float) $a - (float) $b) < 0.009;
    }
}
