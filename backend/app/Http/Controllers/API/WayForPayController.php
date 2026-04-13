<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;
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
            abort(422, 'WayForPay is not available for this region. Use FastSpring checkout.');
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
            'provider' => 'wayforpay',
            'amount_uah' => $amountUah,
            'amount_usd_cents' => $usdCents,
            'status' => 'pending',
            'meta' => [],
        ]);

        $frontend = (string) config('reelforge.frontend_url', '');
        $appUrl = rtrim((string) config('app.url', ''), '/');

        $returnUrl = $frontend !== '' ? $frontend.'/app/credits?payment=return' : url('/app/credits');
        $serviceUrl = $appUrl.'/api/payments/wayforpay/callback';

        $fields = $this->wayForPay->buildPurchaseFormFields(
            $orderReference,
            $package,
            $amountUah,
            $user->email,
            $returnUrl,
            $serviceUrl,
        );

        $fieldPairs = $this->wayForPay->flattenFormFieldsForHtml($fields);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'pay_url' => $this->wayForPay->payUrl(),
                'order_reference' => $orderReference,
                'payment_order_id' => $order->id,
                'amount_uah' => $amountUah,
                'amount_usd_cents' => $usdCents,
                'fields' => $fieldPairs,
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
        $order = PaymentOrder::query()->where('order_reference', $orderReference)->first();

        if ($order === null) {
            return response()->json(['message' => 'Order not found'], 404);
        }

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

        $status = (string) ($data['transactionStatus'] ?? '');

        if ($status === 'Approved') {
            $this->creditService->grantCreditsForWayForPayOrder($order);
        } elseif (in_array($order->status, ['pending'], true)) {
            $order->forceFill([
                'status' => 'failed',
                'meta' => array_merge($order->meta ?? [], ['wayforpay' => $data]),
            ])->save();
        }

        $response = $this->wayForPay->buildServiceAcceptResponse($orderReference);

        return response()->json($response);
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
