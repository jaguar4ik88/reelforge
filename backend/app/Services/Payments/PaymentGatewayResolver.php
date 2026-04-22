<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;

/**
 * Default billing: FastSpring. Ukraine + WayForPay enabled in config → WayForPay.
 */
class PaymentGatewayResolver
{
    public function __construct(
        private readonly WayForPayService $wayForPay,
    ) {}

    /**
     * @return 'wayforpay'|'fastspring'
     */
    public function resolveBillingProvider(Request $request): string
    {
        if ($this->shouldUseWayForPay($request)) {
            return 'wayforpay';
        }

        return 'fastspring';
    }

    public function shouldUseWayForPay(Request $request): bool
    {
        if (! $this->wayForPay->enabled()) {
            return false;
        }

        if (filter_var(config('platform.payments.wayforpay_billing_global', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if (! filter_var(config('platform.payments.wayforpay_for_ukraine_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return $this->detectCountryCode($request) === 'UA';
    }

    /**
     * ISO 3166-1 alpha-2 from trusted proxy headers (Cloudflare, etc.).
     */
    public function detectCountryCode(Request $request): ?string
    {
        $headers = [
            'CF-IPCountry',
            'X-Country-Code',
            'X-Geo-Country',
            'CloudFront-Viewer-Country',
        ];

        foreach ($headers as $header) {
            $v = $request->header($header);
            if (is_string($v) && preg_match('/^[A-Za-z]{2}$/', $v)) {
                return strtoupper($v);
            }
        }

        return null;
    }
}
