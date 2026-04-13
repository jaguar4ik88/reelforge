<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use App\Services\Credits\CreditService;
use App\Services\Payments\FastSpringService;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\WayForPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FastSpringController extends Controller
{
    public function __construct(
        private readonly FastSpringService $fastSpring,
        private readonly CreditService $creditService,
        private readonly PaymentGatewayResolver $gatewayResolver,
    ) {}

    /**
     * Authenticated: which provider applies to this request (IP / proxy headers).
     */
    public function checkoutContext(Request $request): JsonResponse
    {
        $provider = $this->gatewayResolver->resolveBillingProvider($request);

        $wfp = app(WayForPayService::class);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'billing_provider' => $provider,
                'country_code' => $this->gatewayResolver->detectCountryCode($request),
                'wayforpay_available' => $provider === 'wayforpay' && $wfp->enabled(),
                'fastspring_available' => $provider === 'fastspring' && $this->fastSpring->enabled(),
                'usd_to_uah' => (float) config('reelforge.payments.wayforpay.usd_to_uah', 42),
                'ua_discount_percent' => (float) config('reelforge.payments.wayforpay.ua_discount_percent', 0),
            ],
        ]);
    }

    /**
     * Start FastSpring hosted checkout (redirect browser to webcheckoutUrl).
     */
    public function session(Request $request): JsonResponse
    {
        if (! $this->fastSpring->enabled()) {
            abort(503, 'FastSpring is not configured.');
        }

        if ($this->gatewayResolver->resolveBillingProvider($request) !== 'fastspring') {
            abort(422, 'FastSpring checkout is not available for this region. Use WayForPay.');
        }

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:64'],
        ]);

        $package = CreditPackage::query()
            ->where('slug', $validated['slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $productPath = $this->fastSpring->productPathForPackageSlug($package->slug);
        if ($productPath === null) {
            abort(503, 'FastSpring product path is not mapped for this package.');
        }

        $user = $request->user();
        $usdCents = (int) $package->price_cents;
        $orderReference = 'rffs_'.Str::lower(Str::random(28));

        $order = PaymentOrder::query()->create([
            'order_reference' => $orderReference,
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'provider' => 'fastspring',
            'amount_uah' => null,
            'amount_usd_cents' => $usdCents,
            'status' => 'pending',
            'meta' => ['package_slug' => $package->slug],
        ]);

        $buyerIp = $request->ip();
        $result = $this->fastSpring->createCheckoutSession(
            $orderReference,
            $package->slug,
            $productPath,
            (int) $user->id,
            $buyerIp
        );

        if (! ($result['ok'] ?? false)) {
            $msg = $result['message'] ?? 'Could not create FastSpring checkout session.';
            $order->forceFill([
                'status' => 'failed',
                'meta' => array_merge($order->meta ?? [], ['error' => 'fastspring_session_failed', 'message' => $msg]),
            ])->save();
            abort(502, $msg);
        }

        $order->forceFill([
            'meta' => array_merge($order->meta ?? [], [
                'fastspring_session' => $result['session'] ?? [],
            ]),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'checkout_url' => $result['webcheckoutUrl'],
                'order_reference' => $orderReference,
                'payment_order_id' => $order->id,
            ],
        ]);
    }

    /**
     * FastSpring webhook (order.completed, etc.) — verify HMAC, grant credits.
     */
    public function webhook(Request $request): Response
    {
        if ($this->fastSpring->webhookSecret() === '') {
            return response('Not configured', 503);
        }

        $raw = $request->getContent();

        if (! $this->fastSpring->verifyWebhookSignature($raw, $request->header('X-FS-Signature') ?? $request->header('x-fs-signature'))) {
            return response('Invalid signature', 403);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response('Bad JSON', 400);
        }

        $chunks = $this->normalizeWebhookPayloads($payload);

        foreach ($chunks as $chunk) {
            $ref = $this->fastSpring->extractOrderReferenceFromWebhookPayload($chunk);
            if ($ref === null) {
                Log::warning('FastSpring webhook: order.completed payload has no rf_order_ref (orderTags from session must appear on the order)', [
                    'top_keys' => array_keys($chunk),
                ]);

                continue;
            }

            $order = PaymentOrder::query()->where('order_reference', $ref)->where('provider', 'fastspring')->first();
            if ($order === null) {
                Log::warning('FastSpring webhook: unknown order reference', ['ref' => $ref]);

                continue;
            }

            $this->creditService->grantCreditsForFastspringOrder($order);
        }

        return response('OK', 200);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeWebhookPayloads(array $payload): array
    {
        if (isset($payload['events']) && is_array($payload['events'])) {
            $out = [];
            foreach ($payload['events'] as $ev) {
                if (! is_array($ev)) {
                    continue;
                }
                $type = strtolower(trim((string) ($ev['type'] ?? '')));
                if ($type === 'order.completed' && isset($ev['data']) && is_array($ev['data'])) {
                    $out[] = $ev['data'];
                }
            }

            return $out !== [] ? $out : [$payload];
        }

        return [$payload];
    }
}
