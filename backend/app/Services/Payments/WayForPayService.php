<?php

namespace App\Services\Payments;

use App\Models\CreditPackage;
use App\Models\SubscriptionPlan;

class WayForPayService
{
    public function isConfigured(): bool
    {
        return $this->merchantAccount() !== ''
            && $this->secretKey() !== ''
            && $this->merchantDomainName() !== '';
    }

    public function enabled(): bool
    {
        return (bool) config('reelforge.payments.wayforpay.enabled', false) && $this->isConfigured();
    }

    public function merchantAccount(): string
    {
        return (string) config('reelforge.payments.wayforpay.merchant_account', '');
    }

    public function secretKey(): string
    {
        return (string) config('reelforge.payments.wayforpay.secret_key', '');
    }

    public function merchantDomainName(): string
    {
        return (string) config('reelforge.payments.wayforpay.merchant_domain_name', '');
    }

    public function payUrl(): string
    {
        return rtrim((string) config('reelforge.payments.wayforpay.pay_url', 'https://secure.wayforpay.com/pay'), '/');
    }

    /**
     * USD minor units → UAH amount for WayForPay (UA discount applied to list price).
     */
    public function usdCentsToAmountUah(int $usdCents): string
    {
        $usd = $usdCents / 100;
        $rate = (float) config('reelforge.payments.wayforpay.usd_to_uah', 42.0);
        $disc = (float) config('reelforge.payments.wayforpay.ua_discount_percent', 0);
        $factor = max(0.0, 1.0 - ($disc / 100.0));
        $uah = $usd * $rate * $factor;

        return number_format(round($uah, 2), 2, '.', '');
    }

    /**
     * @param  array<int, string>  $productNames
     * @param  array<int, int>  $productCounts
     * @param  array<int, string>  $productPrices  Same format as amount (e.g. "123.45")
     */
    public function signPurchaseRequest(
        string $orderReference,
        string $orderDate,
        string $amount,
        string $currency,
        array $productNames,
        array $productCounts,
        array $productPrices,
    ): string {
        $parts = [
            $this->merchantAccount(),
            $this->merchantDomainName(),
            $orderReference,
            $orderDate,
            $amount,
            $currency,
        ];
        foreach ($productNames as $n) {
            $parts[] = $n;
        }
        foreach ($productCounts as $c) {
            $parts[] = (string) $c;
        }
        foreach ($productPrices as $p) {
            $parts[] = $p;
        }

        return hash_hmac('md5', implode(';', $parts), $this->secretKey());
    }

    /**
     * Verify serviceUrl callback signature (JSON body fields).
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyServiceCallback(array $data): bool
    {
        $expected = $data['merchantSignature'] ?? null;
        if (! is_string($expected) || $expected === '') {
            return false;
        }

        $string = implode(';', [
            (string) ($data['merchantAccount'] ?? ''),
            (string) ($data['orderReference'] ?? ''),
            $this->normalizeAmountForSignature($data['amount'] ?? null),
            (string) ($data['currency'] ?? ''),
            (string) ($data['authCode'] ?? ''),
            (string) ($data['cardPan'] ?? ''),
            (string) ($data['transactionStatus'] ?? ''),
            (string) ($data['reasonCode'] ?? ''),
        ]);

        $calc = hash_hmac('md5', $string, $this->secretKey());

        return hash_equals(strtolower($calc), strtolower($expected));
    }

    private function normalizeAmountForSignature(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '0.00';
        }
        if (is_numeric($amount)) {
            return number_format((float) $amount, 2, '.', '');
        }

        return (string) $amount;
    }

    /**
     * Response body for WayForPay serviceUrl (must be JSON with signature).
     */
    public function buildServiceAcceptResponse(string $orderReference): array
    {
        $time = time();
        $sign = hash_hmac('md5', $orderReference.';accept;'.$time, $this->secretKey());

        return [
            'orderReference' => $orderReference,
            'status' => 'accept',
            'time' => $time,
            'signature' => $sign,
        ];
    }

    /**
     * Build POST fields for https://secure.wayforpay.com/pay
     *
     * @return array<string, string|array<int, string>>
     */
    public function buildPurchaseFormFields(
        string $orderReference,
        CreditPackage $package,
        string $amountUah,
        ?string $clientEmail,
        string $returnUrl,
        string $serviceUrl,
    ): array {
        $orderDate = (string) time();
        $currency = 'UAH';
        $names = [$package->name];
        $counts = [1];
        $prices = [$amountUah];

        $signature = $this->signPurchaseRequest(
            $orderReference,
            $orderDate,
            $amountUah,
            $currency,
            $names,
            $counts,
            $prices,
        );

        $fields = [
            'merchantAccount' => $this->merchantAccount(),
            'merchantAuthType' => 'SimpleSignature',
            'merchantDomainName' => $this->merchantDomainName(),
            'merchantTransactionSecureType' => 'AUTO',
            'merchantSignature' => $signature,
            'apiVersion' => '1',
            'language' => 'UA',
            'orderReference' => $orderReference,
            'orderDate' => $orderDate,
            'amount' => $amountUah,
            'currency' => $currency,
            'productName' => $names,
            'productCount' => $counts,
            'productPrice' => $prices,
            'returnUrl' => $returnUrl,
            'serviceUrl' => $serviceUrl,
        ];

        if ($clientEmail !== null && $clientEmail !== '') {
            $fields['clientEmail'] = $clientEmail;
        }

        return $fields;
    }

    /**
     * POST fields for subscription (monthly regular payment) — same /pay URL as one-off purchase.
     *
     * @return array<string, string|array<int, string>>
     */
    public function buildSubscriptionPurchaseFormFields(
        string $orderReference,
        SubscriptionPlan $plan,
        string $amountUah,
        ?string $clientEmail,
        string $returnUrl,
        string $serviceUrl,
        string $clientAccountId,
    ): array {
        $orderDate = (string) time();
        $currency = 'UAH';
        $label = $plan->name.' — '.$plan->monthly_credits.' credits / mo';
        $names = [$label];
        $counts = [1];
        $prices = [$amountUah];

        $signature = $this->signPurchaseRequest(
            $orderReference,
            $orderDate,
            $amountUah,
            $currency,
            $names,
            $counts,
            $prices,
        );

        $fields = [
            'merchantAccount' => $this->merchantAccount(),
            'merchantAuthType' => 'SimpleSignature',
            'merchantDomainName' => $this->merchantDomainName(),
            'merchantTransactionSecureType' => 'AUTO',
            'merchantSignature' => $signature,
            'apiVersion' => '1',
            'language' => 'UA',
            'orderReference' => $orderReference,
            'orderDate' => $orderDate,
            'amount' => $amountUah,
            'currency' => $currency,
            'productName' => $names,
            'productCount' => $counts,
            'productPrice' => $prices,
            'returnUrl' => $returnUrl,
            'serviceUrl' => $serviceUrl,
            'regularMode' => 'monthly',
            'regularAmount' => $amountUah,
            'regularBehavior' => 'preset',
            'regularOn' => '1',
            'clientAccountId' => $clientAccountId,
        ];

        if ($clientEmail !== null && $clientEmail !== '') {
            $fields['clientEmail'] = $clientEmail;
        }

        return $fields;
    }

    /**
     * Flat list for HTML form: name/value pairs (productName[] etc.).
     *
     * @param  array<string, string|array<int, string|int>>  $fields
     * @return list<array{name: string, value: string}>
     */
    public function flattenFormFieldsForHtml(array $fields): array
    {
        $out = [];
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $suffix = str_ends_with($key, '[]') ? $key : $key.'[]';
                foreach ($value as $item) {
                    $out[] = ['name' => $suffix, 'value' => (string) $item];
                }
            } else {
                $out[] = ['name' => $key, 'value' => (string) $value];
            }
        }

        return $out;
    }
}
