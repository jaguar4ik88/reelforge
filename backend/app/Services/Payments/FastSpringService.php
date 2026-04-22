<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FastSpringService
{
    public function enabled(): bool
    {
        return filter_var(config('platform.payments.fastspring.enabled', true), FILTER_VALIDATE_BOOLEAN)
            && $this->apiUsername() !== ''
            && $this->apiPassword() !== ''
            && $this->checkoutPath() !== '';
    }

    public function apiUsername(): string
    {
        return (string) config('platform.payments.fastspring.api_username', '');
    }

    public function apiPassword(): string
    {
        return (string) config('platform.payments.fastspring.api_password', '');
    }

    public function checkoutPath(): string
    {
        $raw = trim((string) config('platform.payments.fastspring.checkout_path', ''), '/');

        return $this->normalizeCheckoutPathConfig($raw);
    }

    /**
     * Use store-id/checkout-id where store-id is the subdomain part (e.g. aimaglo.test), not the full host
     * (aimaglo.test.onfastspring.com). Otherwise FastSpring builds buyer URLs as https://{store-id}.onfastspring.com/...
     * and the hostname gets doubled.
     */
    private function normalizeCheckoutPathConfig(string $path): string
    {
        if ($path === '' || substr_count($path, '/') !== 1) {
            return $path;
        }
        [$store, $checkout] = explode('/', $path, 2);
        $suffix = '.onfastspring.com';
        if (strlen($store) > strlen($suffix) && str_ends_with(strtolower($store), $suffix)) {
            $store = substr($store, 0, -strlen($suffix));
        }
        /* Mis-typed store id: aimaglo.test.test → aimaglo.test (avoids *.test.test.onfastspring.com hostnames). */
        if (preg_match('/\.test\.test$/', $store) === 1) {
            $store = (string) preg_replace('/\.test\.test$/', '.test', $store);
        }

        return $store.'/'.$checkout;
    }

    /**
     * Defensive fix if webcheckoutUrl still contains duplicated .onfastspring.com (misconfigured store segment).
     */
    private function normalizeWebcheckoutUrl(string $url): string
    {
        $url = str_replace('.onfastspring.com.test.onfastspring.com', '.onfastspring.com', $url);
        $url = str_replace('.test.test.onfastspring.com', '.test.onfastspring.com', $url);

        return preg_replace('#(\.onfastspring\.com)\.onfastspring\.com(?=/|\?|$)#i', '$1', $url) ?? $url;
    }

    /**
     * Classic optional param: overrides “Continue shopping” / catalog destination (see FastSpring optional URL parameters).
     */
    private function appendCatalogContinuationUrl(string $checkoutUrl): string
    {
        if (! filter_var(config('platform.payments.fastspring.append_catalog_to_checkout_url', true), FILTER_VALIDATE_BOOLEAN)) {
            return $checkoutUrl;
        }
        $base = rtrim((string) config('platform.frontend_url', ''), '/');
        if ($base === '') {
            return $checkoutUrl;
        }
        $path = (string) config('platform.payments.fastspring.checkout_return_path', 'app/credits?payment=fastspring_return');
        $path = ltrim($path, '/');
        $return = $base.'/'.$path;
        $sep = str_contains($checkoutUrl, '?') ? '&' : '?';

        return $checkoutUrl.$sep.'catalog='.rawurlencode($return);
    }

    public function webhookSecret(): string
    {
        return (string) config('platform.payments.fastspring.webhook_hmac_secret', '');
    }

    public function apiBaseUrl(): string
    {
        return rtrim((string) config('platform.payments.fastspring.api_base_url', 'https://api.fastspring.com'), '/');
    }

    /**
     * Product path in FastSpring catalog for a credit package slug.
     */
    public function productPathForPackageSlug(string $slug): ?string
    {
        $map = config('platform.payments.fastspring.credit_package_products', []);

        return isset($map[$slug]) && is_string($map[$slug]) && $map[$slug] !== ''
            ? $map[$slug]
            : null;
    }

    /**
     * @return array{ok: true, webcheckoutUrl: string, session: array<string, mixed>}|array{ok: false, message: string}
     */
    public function createCheckoutSession(
        string $orderReference,
        string $packageSlug,
        string $productPath,
        int $userId,
        ?string $buyerIp,
    ): array {
        $path = $this->checkoutPath();
        if ($path === '') {
            return ['ok' => false, 'message' => 'FASTSPRING_CHECKOUT_PATH is empty.'];
        }

        /*
         * Docs: checkoutPath = store-id/checkout-id. OpenAPI shows one {checkoutPath} param; encoding as %2F in a single segment
         * is rejected by api.fastspring.com at the edge (Tomcat HTML 400). Splitting into two path segments reaches the API.
         * Do NOT use POST /v2/sessions — not the documented Session v2 create route (404 with Seller credentials).
         * @see https://developer.fastspring.com/reference/createsession
         */
        if (substr_count($path, '/') !== 1) {
            return ['ok' => false, 'message' => 'FASTSPRING_CHECKOUT_PATH must be store-id/checkout-id (exactly one /, e.g. mystore/main).'];
        }

        [$storeId, $checkoutId] = explode('/', $path, 2);
        $url = $this->apiBaseUrl().'/v2/checkouts/'.rawurlencode($storeId).'/'.rawurlencode($checkoutId).'/sessions';

        $payload = [
            'live' => filter_var(config('platform.payments.fastspring.live', true), FILTER_VALIDATE_BOOLEAN),
            'orderTags' => [
                'rf_order_ref' => $orderReference,
                'rf_package_slug' => $packageSlug,
            ],
            'customer' => [
                'externalAccountId' => 'rfu-'.$userId,
            ],
            'cart' => [
                'lineItems' => [
                    [
                        'productPath' => $productPath,
                        'quantity' => 1,
                    ],
                ],
            ],
        ];

        if ($buyerIp !== null && $buyerIp !== '') {
            $payload['buyerIp'] = $buyerIp;
        }

        try {
            $response = Http::withBasicAuth($this->apiUsername(), $this->apiPassword())
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error('FastSpring session request failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'FastSpring request failed: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            $body = $response->body();
            Log::warning('FastSpring session API error', [
                'status' => $response->status(),
                'url' => $url,
                'body' => $body,
            ]);

            return [
                'ok' => false,
                'message' => 'FastSpring API '.$response->status().': '.$this->shortenErrorBody($body).$this->hintForSessionFailure($response->status(), $body),
            ];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['ok' => false, 'message' => 'Invalid JSON from FastSpring.'];
        }

        $checkoutUrl = $this->resolveCheckoutUrlFromSessionResponse($json);
        if (is_string($checkoutUrl) && $checkoutUrl !== '') {
            $checkoutUrl = $this->normalizeWebcheckoutUrl($checkoutUrl);
        }

        if (! is_string($checkoutUrl) || $checkoutUrl === '') {
            Log::warning('FastSpring session missing checkout URL', ['json' => $json]);

            return ['ok' => false, 'message' => 'FastSpring session has no checkout URL (check popup vs web checkout in response).'];
        }

        $checkoutUrl = $this->appendCatalogContinuationUrl($checkoutUrl);

        return [
            'ok' => true,
            'webcheckoutUrl' => $checkoutUrl,
            'session' => $json,
        ];
    }

    /**
     * Web vs popup checkouts may expose different keys under checkoutUrls.
     */
    private function shortenErrorBody(string $body): string
    {
        $trim = trim($body);
        if ($trim !== '' && str_starts_with($trim, '{')) {
            $decoded = json_decode($trim, true);
            if (is_array($decoded)) {
                $msg = $decoded['message'] ?? null;
                $err0 = $decoded['errors'][0] ?? null;
                $detail = is_array($err0) && isset($err0['message']) ? (string) $err0['message'] : '';
                if (is_string($msg) && $msg !== '') {
                    $out = $detail !== '' ? $msg.' — '.$detail : $msg;

                    return strlen($out) > 400 ? substr($out, 0, 400).'…' : $out;
                }
            }
        }

        $body = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $body) ?? $body;
        $body = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $body) ?? $body;
        $body = trim(strip_tags($body));
        $body = preg_replace('/\s+/u', ' ', $body) ?? $body;
        if ($body === '') {
            return '(empty body)';
        }

        return strlen($body) > 400 ? substr($body, 0, 400).'…' : $body;
    }

    private function hintForSessionFailure(int $status, string $body): string
    {
        if ($status === 404 && trim($body) === '') {
            return ' (Use POST /v2/checkouts/{store}/{checkout}/sessions with checkout in two path segments, not POST /v2/sessions.)';
        }

        $looksHtml = $body !== '' && (stripos($body, '<!doctype') !== false || stripos($body, '<html') !== false);
        if ($status === 400 && $looksHtml) {
            return ' (Avoid a single path segment with %2F; use /v2/checkouts/{store}/{checkout}/sessions.)';
        }

        return '';
    }

    private function resolveCheckoutUrlFromSessionResponse(array $json): ?string
    {
        $urls = data_get($json, 'checkoutUrls');
        if (! is_array($urls)) {
            return null;
        }

        foreach (['webcheckoutUrl', 'popupUrl', 'popupCheckoutUrl', 'embeddedCheckoutUrl'] as $key) {
            $v = $urls[$key] ?? null;
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }

        return null;
    }

    public function verifyWebhookSignature(string $rawBody, ?string $headerSignature): bool
    {
        $secret = $this->webhookSecret();
        if ($secret === '' || $headerSignature === null || $headerSignature === '') {
            return false;
        }

        $computed = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($computed, $headerSignature);
    }

    /**
     * Extract our order reference from FastSpring webhook JSON (order.completed payload).
     */
    public function extractOrderReferenceFromWebhookPayload(array $payload): ?string
    {
        $ref = $this->findKeyRecursive($payload, 'rf_order_ref');
        if (is_string($ref) && str_starts_with($ref, 'rffs_')) {
            return $ref;
        }

        return null;
    }

    /**
     * @return mixed
     */
    private function findKeyRecursive(array $node, string $key)
    {
        if (array_key_exists($key, $node)) {
            return $node[$key];
        }

        foreach ($node as $v) {
            if (is_array($v)) {
                $found = $this->findKeyRecursive($v, $key);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Raw body + signature header (call before JSON middleware consumes body if you add dedicated route middleware).
     */
    public function verifyRequest(Request $request): bool
    {
        $sig = $request->header('X-FS-Signature') ?? $request->header('x-fs-signature');

        return $this->verifyWebhookSignature($request->getContent(), is_string($sig) ? $sig : null);
    }
}
