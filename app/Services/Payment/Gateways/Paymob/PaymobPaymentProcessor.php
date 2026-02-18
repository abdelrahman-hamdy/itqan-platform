<?php

namespace App\Services\Payment\Gateways\Paymob;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;

/**
 * Handles standard payment intent creation and payment verification for Paymob.
 */
class PaymobPaymentProcessor
{
    public function __construct(
        protected PaymobApiClient $client,
        protected array $config
    ) {}

    /**
     * Convert amount to EGP if needed, since Paymob Egypt only accepts EGP.
     *
     * @return array ['amount_cents' => int, 'currency' => 'EGP', 'original_amount' => float, 'original_currency' => string, 'exchange_rate' => float|null]
     */
    public function convertToEgpIfNeeded(PaymentIntent $intent): array
    {
        $originalCurrency = $intent->currency;
        $originalAmountCents = $intent->amountInCents;
        $originalAmount = $intent->getAmountInMajorUnits();

        if (strtoupper($originalCurrency) === 'EGP') {
            return [
                'amount_cents' => $originalAmountCents,
                'currency' => 'EGP',
                'original_amount' => $originalAmount,
                'original_currency' => $originalCurrency,
                'exchange_rate' => null,
            ];
        }

        $convertedAmount = convertCurrency($originalAmount, $originalCurrency, 'EGP');
        $convertedAmountCents = (int) round($convertedAmount * 100);
        $exchangeRate = $convertedAmount / $originalAmount;

        Log::channel('payments')->info('Paymob currency conversion applied', [
            'original_amount' => $originalAmount,
            'original_currency' => $originalCurrency,
            'converted_amount' => $convertedAmount,
            'converted_currency' => 'EGP',
            'exchange_rate' => $exchangeRate,
            'payment_id' => $intent->paymentId,
        ]);

        return [
            'amount_cents' => $convertedAmountCents,
            'currency' => 'EGP',
            'original_amount' => $originalAmount,
            'original_currency' => $originalCurrency,
            'exchange_rate' => $exchangeRate,
        ];
    }

    /**
     * Get integration IDs based on payment method.
     */
    public function getIntegrationIds(string $method): array
    {
        $integrations = $this->config['integration_ids'] ?? [];

        return match ($method) {
            'card' => array_filter([(int) ($integrations['card'] ?? 0)]),
            'wallet' => array_filter([(int) ($integrations['wallet'] ?? 0)]),
            'apple_pay' => array_filter([(int) ($integrations['apple_pay'] ?? 0)]),
            'bank_installments' => array_filter([(int) ($integrations['installments'] ?? 0)]),
            'all' => array_filter([
                (int) ($integrations['card'] ?? 0),
                (int) ($integrations['wallet'] ?? 0),
                (int) ($integrations['apple_pay'] ?? 0),
            ]),
            default => array_filter([(int) ($integrations['card'] ?? 0)]),
        };
    }

    /**
     * Build billing data from PaymentIntent.
     */
    public function buildBillingData(PaymentIntent $intent): array
    {
        $billingData = array_merge([
            'first_name' => 'N/A',
            'last_name' => 'N/A',
            'email' => 'na@na.com',
            'phone_number' => 'NA',
            'country' => 'SA',
            'city' => 'NA',
            'street' => 'NA',
            'building' => 'NA',
            'floor' => 'NA',
            'apartment' => 'NA',
        ], $intent->billingData);

        if ($intent->customerName) {
            $names = explode(' ', $intent->customerName, 2);
            $billingData['first_name'] = $names[0];
            $billingData['last_name'] = $names[1] ?? $names[0];
        }
        if ($intent->customerEmail) {
            $billingData['email'] = $intent->customerEmail;
        }
        if ($intent->customerPhone) {
            $billingData['phone_number'] = $intent->customerPhone;
        }

        return $billingData;
    }

    /**
     * Create a payment intent using Unified Intention API.
     */
    public function createPaymentIntent(PaymentIntent $intent, callable $chargeTokenCallback): PaymentResult
    {
        $conversion = $this->convertToEgpIfNeeded($intent);

        Log::channel('payments')->info('PaymobGateway createPaymentIntent called', [
            'has_secret_key' => ! empty($this->config['secret_key']),
            'has_public_key' => ! empty($this->config['public_key']),
            'has_api_key' => ! empty($this->config['api_key']),
            'card_integration_id' => $this->config['integration_ids']['card'] ?? 'not set',
            'base_url' => $this->client->getBaseUrl(),
            'payment_id' => $intent->paymentId,
            'original_amount_cents' => $intent->amountInCents,
            'original_currency' => $intent->currency,
            'charged_amount_cents' => $conversion['amount_cents'],
            'charged_currency' => $conversion['currency'],
            'conversion_applied' => $conversion['exchange_rate'] !== null,
            'exchange_rate' => $conversion['exchange_rate'],
        ]);

        try {
            if (! empty($intent->cardToken)) {
                return $chargeTokenCallback(
                    $intent->cardToken,
                    $conversion['amount_cents'],
                    $conversion['currency'],
                    [
                        'payment_id' => $intent->paymentId,
                        'academy_id' => $intent->academyId,
                        'billing_data' => $intent->billingData,
                        'customer_email' => $intent->customerEmail,
                        'customer_name' => $intent->customerName,
                        'customer_phone' => $intent->customerPhone,
                    ]
                );
            }

            $items = [];
            foreach ($intent->items as $item) {
                $itemAmount = $item['amount'] ?? $conversion['amount_cents'];

                if ($conversion['exchange_rate'] !== null && isset($item['amount'])) {
                    $itemAmountInMajorUnits = $item['amount'] / 100;
                    $convertedItemAmount = convertCurrency($itemAmountInMajorUnits, $conversion['original_currency'], 'EGP');
                    $itemAmount = (int) round($convertedItemAmount * 100);
                }

                $items[] = [
                    'name' => $item['name'] ?? __('payments.service.subscription_label'),
                    'amount' => (int) $itemAmount,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                ];
            }

            if (empty($items)) {
                $items[] = [
                    'name' => $intent->description ?? __('payments.service.subscription_label'),
                    'amount' => (int) $conversion['amount_cents'],
                    'quantity' => 1,
                ];
            }

            $paymentMethods = $this->getIntegrationIds($intent->paymentMethod);

            if (empty($paymentMethods) || $paymentMethods === [0]) {
                Log::channel('payments')->error('Paymob: No valid integration ID configured', [
                    'payment_method' => $intent->paymentMethod,
                    'integration_ids_config' => $this->config['integration_ids'] ?? [],
                ]);

                return PaymentResult::failed(
                    errorCode: 'NO_INTEGRATION_ID',
                    errorMessage: 'No valid Paymob integration ID configured for payment method: '.$intent->paymentMethod,
                    errorMessageAr: __('payments.paymob.no_valid_integration'),
                );
            }

            $billingData = $this->buildBillingData($intent);

            $merchantOrderId = sprintf(
                '%d-%d-%d',
                $intent->academyId,
                $intent->paymentId ?? 0,
                time()
            );

            $requestBody = [
                'amount' => $conversion['amount_cents'],
                'currency' => $conversion['currency'],
                'payment_methods' => $paymentMethods,
                'items' => $items,
                'billing_data' => $billingData,
                'extras' => [
                    'payment_id' => $intent->paymentId,
                    'academy_id' => $intent->academyId,
                    'save_card' => $intent->saveCard ?? false,
                ],
                'special_reference' => $merchantOrderId,
            ];

            if ($intent->saveCard ?? false) {
                $requestBody['save_card'] = true;
            }

            if ($intent->successUrl) {
                $requestBody['redirection_url'] = $intent->successUrl;
            }
            if ($intent->webhookUrl) {
                $requestBody['notification_url'] = $intent->webhookUrl;
            }

            Log::channel('payments')->info('Paymob: Making intention API request', [
                'endpoint' => '/v1/intention/',
                'payment_methods' => $paymentMethods,
                'amount' => $requestBody['amount'],
                'currency' => $requestBody['currency'],
                'items' => $requestBody['items'],
            ]);

            $response = $this->client->request('POST', '/v1/intention/', $requestBody, [
                'Authorization' => 'Token '.$this->config['secret_key'],
            ]);

            Log::channel('payments')->info('Paymob: Intention API response', [
                'success' => $response['success'],
                'has_data' => isset($response['data']),
                'has_client_secret' => isset($response['data']['client_secret']),
                'has_id' => isset($response['data']['id']),
                'error' => $response['error'] ?? null,
            ]);

            if (! $response['success']) {
                Log::channel('payments')->error('Paymob intention failed', [
                    'response' => $response,
                    'intent' => $intent->toSafeLogArray(),
                ]);

                return PaymentResult::failed(
                    errorCode: 'INTENTION_FAILED',
                    errorMessage: $response['error'] ?? 'Failed to create payment intention',
                    errorMessageAr: __('payments.paymob.create_intent_failed'),
                    rawResponse: $response['data'] ?? [],
                );
            }

            $data = $response['data'];
            $clientSecret = $data['client_secret'] ?? null;
            $intentionId = $data['id'] ?? null;
            $paymentKeys = $data['payment_keys'] ?? [];

            $iframeUrl = null;
            $publicKey = $this->config['public_key'] ?? null;

            if ($clientSecret && $publicKey) {
                $iframeUrl = sprintf(
                    '%s/unifiedcheckout/?publicKey=%s&clientSecret=%s',
                    $this->client->getBaseUrl(),
                    $publicKey,
                    $clientSecret
                );
            }

            Log::channel('payments')->info('Paymob: Payment result prepared', [
                'intention_id' => $intentionId,
                'has_client_secret' => ! empty($clientSecret),
                'has_public_key' => ! empty($publicKey),
                'iframe_url' => $iframeUrl ? (substr($iframeUrl, 0, 80).'...') : 'NOT BUILT',
            ]);

            return PaymentResult::pending(
                transactionId: (string) $intentionId,
                iframeUrl: $iframeUrl,
                clientSecret: $clientSecret,
                paymentKeys: $paymentKeys,
                rawResponse: $data,
                metadata: [
                    'intention_id' => $intentionId,
                    'merchant_order_id' => $merchantOrderId,
                    'save_card_requested' => $intent->saveCard ?? false,
                ],
            );
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob exception', [
                'message' => $e->getMessage(),
                'intent' => $intent->toSafeLogArray(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.paymob.unexpected_error'),
            );
        }
    }

    /**
     * Verify a payment after callback.
     */
    public function verifyPayment(string $transactionId, array $data = []): PaymentResult
    {
        try {
            $authToken = $this->client->getAuthToken();
            if (! $authToken) {
                return PaymentResult::failed(
                    errorCode: 'AUTH_FAILED',
                    errorMessage: 'Failed to authenticate with Paymob',
                    errorMessageAr: __('payments.paymob.auth_check_failed'),
                    transactionId: $transactionId,
                );
            }

            $response = $this->client->request('GET', "/api/acceptance/transactions/{$transactionId}", [], [
                'Authorization' => 'Bearer '.$authToken,
            ]);

            if (! $response['success']) {
                return PaymentResult::failed(
                    errorCode: 'VERIFICATION_FAILED',
                    errorMessage: 'Failed to verify payment',
                    errorMessageAr: __('payments.paymob.verify_failed'),
                    transactionId: $transactionId,
                    rawResponse: $response['data'] ?? [],
                );
            }

            $txn = $response['data'];

            if ($txn['success'] === true) {
                $metadata = [
                    'amount_cents' => $txn['amount_cents'],
                    'currency' => $txn['currency'],
                    'source_type' => $txn['source_data']['type'] ?? 'card',
                ];

                if (! empty($txn['source_data']['token'])) {
                    $metadata['card_token'] = $txn['source_data']['token'];
                    $metadata['card_brand'] = $txn['source_data']['sub_type'] ?? null;
                    $metadata['card_last_four'] = substr($txn['source_data']['pan'] ?? '', -4) ?: null;
                }

                return PaymentResult::success(
                    transactionId: $transactionId,
                    gatewayOrderId: (string) ($txn['order']['id'] ?? ''),
                    rawResponse: $txn,
                    metadata: $metadata,
                );
            }

            return PaymentResult::failed(
                errorCode: $txn['data']['txn_response_code'] ?? 'DECLINED',
                errorMessage: $txn['data']['message'] ?? 'Payment was declined',
                errorMessageAr: __('payments.paymob.payment_declined'),
                transactionId: $transactionId,
                rawResponse: $txn,
            );
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob verification exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.paymob.verify_error'),
                transactionId: $transactionId,
            );
        }
    }
}
