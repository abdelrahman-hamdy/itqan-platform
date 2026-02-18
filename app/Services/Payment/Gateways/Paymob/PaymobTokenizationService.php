<?php

namespace App\Services\Payment\Gateways\Paymob;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\SavedPaymentMethod;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\TokenizationResult;

/**
 * Handles card tokenization, token-based charges, and recurring payments for Paymob.
 */
class PaymobTokenizationService
{
    public function __construct(
        protected PaymobApiClient $client,
        protected array $config
    ) {}

    /**
     * Check if tokenization is properly configured.
     */
    public function supportsTokenization(): bool
    {
        $tokenizationEnabled = $this->config['tokenization']['enabled'] ?? true;
        $hasApiKey = ! empty($this->config['api_key']);
        $hasSecretKey = ! empty($this->config['secret_key']);

        return $tokenizationEnabled && $hasApiKey && $hasSecretKey;
    }

    /**
     * Get tokenization iframe URL for adding a card without payment.
     */
    public function getTokenizationIframeUrl(int $userId, array $options = []): array
    {
        try {
            $billingData = [
                'first_name' => $options['first_name'] ?? 'User',
                'last_name' => $options['last_name'] ?? (string) $userId,
                'email' => $options['email'] ?? config('app.fallback_email', 'noreply@itqanway.com'),
                'phone_number' => $options['phone'] ?? config('payments.gateways.paymob.fallback_billing.phone', '+201000000000'),
                'country' => $options['country'] ?? config('payments.gateways.paymob.fallback_billing.country', 'EGY'),
                'city' => $options['city'] ?? config('payments.gateways.paymob.fallback_billing.city', 'Cairo'),
                'street' => $options['street'] ?? 'NA',
                'building' => $options['building'] ?? 'NA',
                'floor' => $options['floor'] ?? 'NA',
                'apartment' => $options['apartment'] ?? 'NA',
            ];

            $requestBody = [
                'amount' => 100,
                'currency' => 'EGP',
                'payment_methods' => [(int) ($this->config['integration_ids']['card'] ?? 0)],
                'items' => [
                    [
                        'name' => 'Card Tokenization',
                        'amount' => 100,
                        'quantity' => 1,
                    ],
                ],
                'billing_data' => $billingData,
                'extras' => [
                    'user_id' => $userId,
                    'academy_id' => $options['academy_id'] ?? null,
                    'tokenization_only' => true,
                ],
                'save_card' => true,
                'special_reference' => sprintf('TOKEN-%d-%d', $userId, time()),
            ];

            if (! empty($options['callback_url'])) {
                $requestBody['redirection_url'] = $options['callback_url'];
            }

            $response = $this->client->request('POST', '/v1/intention/', $requestBody, [
                'Authorization' => 'Token '.$this->config['secret_key'],
            ]);

            if (! $response['success']) {
                Log::channel('payments')->error('Paymob tokenization intention failed', [
                    'response' => $response,
                    'user_id' => $userId,
                ]);

                return [
                    'success' => false,
                    'error' => $response['error'] ?? __('student.saved_payment_methods.tokenization_request_error'),
                ];
            }

            $data = $response['data'];
            $clientSecret = $data['client_secret'] ?? null;

            if (! $clientSecret) {
                return [
                    'success' => false,
                    'error' => __('student.saved_payment_methods.tokenization_session_error'),
                ];
            }

            $iframeUrl = sprintf(
                '%s/unifiedcheckout/?publicKey=%s&clientSecret=%s',
                $this->client->getBaseUrl(),
                $this->config['public_key'],
                $clientSecret
            );

            return [
                'success' => true,
                'iframe_url' => $iframeUrl,
                'client_secret' => $clientSecret,
                'intention_id' => $data['id'] ?? null,
            ];
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob tokenization iframe exception', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => __('student.saved_payment_methods.load_form_error'),
            ];
        }
    }

    /**
     * Tokenize a card and get a reusable token.
     */
    public function tokenizeCard(array $cardData, int $userId): TokenizationResult
    {
        try {
            $authToken = $this->client->getAuthToken();
            if (! $authToken) {
                return TokenizationResult::failed(
                    errorCode: 'AUTH_FAILED',
                    errorMessage: 'Failed to authenticate with Paymob',
                    errorMessageAr: __('payments.paymob.auth_failed'),
                );
            }

            $orderResponse = $this->client->request('POST', '/api/ecommerce/orders', [
                'auth_token' => $authToken,
                'delivery_needed' => false,
                'amount_cents' => 100,
                'currency' => 'EGP',
                'items' => [],
            ]);

            if (! $orderResponse['success']) {
                return TokenizationResult::failed(
                    errorCode: 'ORDER_FAILED',
                    errorMessage: 'Failed to create verification order',
                    errorMessageAr: __('payments.paymob.create_verify_failed'),
                    rawResponse: $orderResponse['data'] ?? [],
                );
            }

            $orderId = $orderResponse['data']['id'] ?? null;

            $paymentKeyResponse = $this->client->request('POST', '/api/acceptance/payment_keys', [
                'auth_token' => $authToken,
                'amount_cents' => 100,
                'expiration' => 3600,
                'order_id' => $orderId,
                'billing_data' => [
                    'first_name' => $cardData['holder_name'] ?? 'N/A',
                    'last_name' => 'N/A',
                    'email' => $cardData['email'] ?? 'na@na.com',
                    'phone_number' => $cardData['phone'] ?? 'NA',
                    'country' => 'EG',
                    'city' => 'NA',
                    'street' => 'NA',
                    'building' => 'NA',
                    'floor' => 'NA',
                    'apartment' => 'NA',
                ],
                'currency' => 'EGP',
                'integration_id' => $this->config['integration_ids']['card'] ?? 0,
                'lock_order_when_paid' => false,
            ]);

            if (! $paymentKeyResponse['success']) {
                return TokenizationResult::failed(
                    errorCode: 'PAYMENT_KEY_FAILED',
                    errorMessage: 'Failed to get payment key',
                    errorMessageAr: __('payments.paymob.payment_key_failed'),
                    rawResponse: $paymentKeyResponse['data'] ?? [],
                );
            }

            $paymentKey = $paymentKeyResponse['data']['token'] ?? null;

            $tokenResponse = $this->client->request('POST', '/api/acceptance/tokens', [
                'payment_token' => $paymentKey,
                'card_number' => $cardData['number'],
                'card_holdername' => $cardData['holder_name'],
                'card_expiry_mm' => $cardData['expiry_month'],
                'card_expiry_yy' => $cardData['expiry_year'],
                'card_cvn' => $cardData['cvv'],
            ]);

            if (! $tokenResponse['success'] || empty($tokenResponse['data']['token'])) {
                return TokenizationResult::failed(
                    errorCode: 'TOKENIZATION_FAILED',
                    errorMessage: $tokenResponse['data']['message'] ?? 'Failed to tokenize card',
                    errorMessageAr: __('payments.error_codes.save_card_failed'),
                    rawResponse: $tokenResponse['data'] ?? [],
                );
            }

            $tokenData = $tokenResponse['data'];

            return TokenizationResult::success(
                token: $tokenData['token'],
                cardBrand: $tokenData['card_subtype'] ?? $tokenData['masked_pan'] ? $this->detectCardBrand($tokenData['masked_pan']) : null,
                lastFour: substr($tokenData['masked_pan'] ?? '', -4) ?: null,
                expiryMonth: $cardData['expiry_month'],
                expiryYear: $cardData['expiry_year'],
                holderName: $cardData['holder_name'] ?? null,
                rawResponse: $tokenData,
            );
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob tokenization exception', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);

            return TokenizationResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.method_service.save_card_error'),
            );
        }
    }

    /**
     * Charge a tokenized card directly.
     */
    public function chargeToken(string $token, int $amountInCents, string $currency, array $metadata = []): PaymentResult
    {
        try {
            $authToken = $this->client->getAuthToken();
            if (! $authToken) {
                return PaymentResult::failed(
                    errorCode: 'AUTH_FAILED',
                    errorMessage: 'Failed to authenticate with Paymob',
                    errorMessageAr: __('payments.paymob.auth_failed'),
                );
            }

            $merchantOrderId = sprintf(
                '%d-%d-%d',
                $metadata['academy_id'] ?? 0,
                $metadata['payment_id'] ?? 0,
                time()
            );

            $orderResponse = $this->client->request('POST', '/api/ecommerce/orders', [
                'auth_token' => $authToken,
                'delivery_needed' => false,
                'amount_cents' => $amountInCents,
                'currency' => $currency,
                'merchant_order_id' => $merchantOrderId,
                'items' => [],
            ]);

            if (! $orderResponse['success']) {
                return PaymentResult::failed(
                    errorCode: 'ORDER_FAILED',
                    errorMessage: 'Failed to create order',
                    errorMessageAr: __('payments.paymob.create_order_failed'),
                    rawResponse: $orderResponse['data'] ?? [],
                );
            }

            $orderId = $orderResponse['data']['id'] ?? null;

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
            ], $metadata['billing_data'] ?? []);

            if (! empty($metadata['customer_name'])) {
                $names = explode(' ', $metadata['customer_name'], 2);
                $billingData['first_name'] = $names[0];
                $billingData['last_name'] = $names[1] ?? $names[0];
            }
            if (! empty($metadata['customer_email'])) {
                $billingData['email'] = $metadata['customer_email'];
            }
            if (! empty($metadata['customer_phone'])) {
                $billingData['phone_number'] = $metadata['customer_phone'];
            }

            $paymentKeyResponse = $this->client->request('POST', '/api/acceptance/payment_keys', [
                'auth_token' => $authToken,
                'amount_cents' => $amountInCents,
                'expiration' => 3600,
                'order_id' => $orderId,
                'billing_data' => $billingData,
                'currency' => $currency,
                'integration_id' => $this->config['integration_ids']['card'] ?? 0,
            ]);

            if (! $paymentKeyResponse['success']) {
                return PaymentResult::failed(
                    errorCode: 'PAYMENT_KEY_FAILED',
                    errorMessage: 'Failed to get payment key',
                    errorMessageAr: __('payments.paymob.payment_key_failed'),
                    rawResponse: $paymentKeyResponse['data'] ?? [],
                );
            }

            $paymentKey = $paymentKeyResponse['data']['token'] ?? null;

            $payResponse = $this->client->request('POST', '/api/acceptance/payments/pay', [
                'source' => [
                    'identifier' => $token,
                    'subtype' => 'TOKEN',
                ],
                'payment_token' => $paymentKey,
            ]);

            if (! $payResponse['success']) {
                return PaymentResult::failed(
                    errorCode: 'PAYMENT_FAILED',
                    errorMessage: $payResponse['data']['message'] ?? 'Payment failed',
                    errorMessageAr: __('payments.paymob.payment_failed'),
                    rawResponse: $payResponse['data'] ?? [],
                );
            }

            $txnData = $payResponse['data'];

            if ($txnData['success'] === true) {
                return PaymentResult::success(
                    transactionId: (string) ($txnData['id'] ?? ''),
                    gatewayOrderId: (string) $orderId,
                    rawResponse: $txnData,
                    metadata: [
                        'amount_cents' => $amountInCents,
                        'currency' => $currency,
                        'is_token_payment' => true,
                        'merchant_order_id' => $merchantOrderId,
                    ],
                );
            }

            return PaymentResult::failed(
                errorCode: $txnData['data']['txn_response_code'] ?? 'DECLINED',
                errorMessage: $txnData['data']['message'] ?? 'Payment was declined',
                errorMessageAr: __('payments.paymob.payment_declined'),
                transactionId: (string) ($txnData['id'] ?? ''),
                rawResponse: $txnData,
            );
        } catch (Exception $e) {
            Log::channel('payments')->error('Paymob token charge exception', [
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.paymob.payment_error'),
            );
        }
    }

    /**
     * Delete a saved token from Paymob.
     */
    public function deleteToken(string $token): bool
    {
        Log::channel('payments')->info('Paymob token marked for deletion', [
            'token_prefix' => substr($token, 0, 10).'...',
        ]);

        return true;
    }

    /**
     * Get details about a tokenized card.
     */
    public function getTokenDetails(string $token): ?array
    {
        return null;
    }

    /**
     * Check if recurring payments are supported.
     */
    public function supportsRecurring(): bool
    {
        return $this->supportsTokenization();
    }

    /**
     * Get minimum interval between recurring charges.
     */
    public function getMinimumRecurringInterval(): int
    {
        return 0;
    }

    /**
     * Charge a saved payment method for recurring billing.
     */
    public function chargeSavedPaymentMethod(
        SavedPaymentMethod $paymentMethod,
        int $amountInCents,
        string $currency,
        array $metadata = []
    ): PaymentResult {
        if (! $paymentMethod->isUsable()) {
            return PaymentResult::failed(
                errorCode: 'PAYMENT_METHOD_UNUSABLE',
                errorMessage: 'Payment method is not usable',
                errorMessageAr: __('payments.paymob.invalid_saved_method'),
            );
        }

        $metadata = array_merge([
            'customer_name' => $paymentMethod->holder_name,
            'billing_data' => $paymentMethod->billing_address ?? [],
        ], $metadata);

        $result = $this->chargeToken(
            $paymentMethod->token,
            $amountInCents,
            $currency,
            $metadata
        );

        if ($result->isSuccessful()) {
            $paymentMethod->touchLastUsed();
        }

        return $result;
    }

    /**
     * Detect card brand from masked PAN.
     */
    public function detectCardBrand(string $maskedPan): ?string
    {
        $firstDigit = substr($maskedPan, 0, 1);
        $firstTwo = substr($maskedPan, 0, 2);

        return match (true) {
            $firstDigit === '4' => 'visa',
            in_array($firstTwo, ['51', '52', '53', '54', '55']) => 'mastercard',
            $firstTwo === '50' || str_starts_with($maskedPan, '507') => 'meeza',
            in_array($firstTwo, ['34', '37']) => 'amex',
            default => null,
        };
    }
}
