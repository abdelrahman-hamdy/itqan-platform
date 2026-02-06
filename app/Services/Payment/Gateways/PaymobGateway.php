<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\Payment\SupportsRecurringPayments;
use App\Contracts\Payment\SupportsRefunds;
use App\Contracts\Payment\SupportsTokenization;
use App\Contracts\Payment\SupportsVoid;
use App\Contracts\Payment\SupportsWebhooks;
use App\Enums\PaymentFlowType;
use App\Models\SavedPaymentMethod;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\TokenizationResult;
use App\Services\Payment\DTOs\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Paymob payment gateway implementation using Unified Intention API.
 *
 * Supports:
 * - Card payments (Visa, Mastercard, Meeza)
 * - Mobile wallets
 * - Apple Pay
 * - Bank installments
 * - Card tokenization for recurring payments
 * - Refunds and voids
 *
 * @see https://developers.paymob.com/egypt/
 */
class PaymobGateway extends AbstractGateway implements
    SupportsRefunds,
    SupportsWebhooks,
    SupportsTokenization,
    SupportsRecurringPayments,
    SupportsVoid
{
    /**
     * Get the gateway identifier name.
     */
    public function getName(): string
    {
        return 'paymob';
    }

    /**
     * Get the human-readable display name (Arabic).
     */
    public function getDisplayName(): string
    {
        return __('payments.gateways.paymob');
    }

    /**
     * Get supported payment methods.
     */
    public function getSupportedMethods(): array
    {
        return ['card', 'wallet', 'bank_installments', 'apple_pay'];
    }

    /**
     * Get the payment flow type.
     */
    public function getFlowType(): PaymentFlowType
    {
        return PaymentFlowType::IFRAME;
    }

    /**
     * Get required configuration keys.
     */
    protected function getRequiredConfigKeys(): array
    {
        return ['secret_key', 'public_key'];
    }

    /**
     * Get the base URL for Paymob API.
     */
    public function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? 'https://accept.paymob.com';
    }

    // ========================================
    // Payment Intent (Standard Payments)
    // ========================================

    /**
     * Create a payment intent using Unified Intention API.
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        // Debug: Log the config being used
        Log::channel('payments')->info('PaymobGateway createPaymentIntent called', [
            'has_secret_key' => ! empty($this->config['secret_key']),
            'has_public_key' => ! empty($this->config['public_key']),
            'has_api_key' => ! empty($this->config['api_key']),
            'card_integration_id' => $this->config['integration_ids']['card'] ?? 'not set',
            'base_url' => $this->getBaseUrl(),
            'payment_id' => $intent->paymentId,
            'amount_cents' => $intent->amountInCents,
            'currency' => $intent->currency,
        ]);

        try {
            // Check if this is a token-based payment
            if (! empty($intent->cardToken)) {
                return $this->chargeToken(
                    $intent->cardToken,
                    $intent->amountInCents,
                    $intent->currency,
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

            // Build items array
            $items = [];
            foreach ($intent->items as $item) {
                $items[] = [
                    'name' => $item['name'] ?? __('payments.service.subscription_label'),
                    'amount' => (int) ($item['amount'] ?? $intent->amountInCents), // Must be integer for Paymob
                    'quantity' => (int) ($item['quantity'] ?? 1),
                ];
            }

            if (empty($items)) {
                $items[] = [
                    'name' => $intent->description ?? __('payments.service.subscription_label'),
                    'amount' => (int) $intent->amountInCents, // Must be integer for Paymob
                    'quantity' => 1,
                ];
            }

            // Get integration IDs for payment methods
            $paymentMethods = $this->getIntegrationIds($intent->paymentMethod);

            // Validate we have at least one valid integration ID
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

            // Build billing data
            $billingData = $this->buildBillingData($intent);

            // Build merchant order ID for tracking
            $merchantOrderId = sprintf(
                '%d-%d-%d',
                $intent->academyId,
                $intent->paymentId ?? 0,
                time()
            );

            // Prepare intention request body
            $requestBody = [
                'amount' => $intent->amountInCents,
                'currency' => $intent->currency,
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

            // Enable tokenization if user wants to save card
            if ($intent->saveCard ?? false) {
                $requestBody['save_card'] = true;
            }

            // Add URLs if provided
            if ($intent->successUrl) {
                $requestBody['redirection_url'] = $intent->successUrl;
            }
            if ($intent->webhookUrl) {
                $requestBody['notification_url'] = $intent->webhookUrl;
            }

            // Make API request
            Log::channel('payments')->info('Paymob: Making intention API request', [
                'endpoint' => '/v1/intention/',
                'payment_methods' => $paymentMethods,
                'amount' => $requestBody['amount'],
                'currency' => $requestBody['currency'],
            ]);

            $response = $this->request('POST', '/v1/intention/', $requestBody, [
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

            // Build iframe URL
            $clientSecret = $data['client_secret'] ?? null;
            $intentionId = $data['id'] ?? null;
            $paymentKeys = $data['payment_keys'] ?? [];

            // Build Unified Checkout URL (does not need iframe_id - only public_key and clientSecret)
            $iframeUrl = null;
            $publicKey = $this->config['public_key'] ?? null;

            if ($clientSecret && $publicKey) {
                $iframeUrl = sprintf(
                    '%s/unifiedcheckout/?publicKey=%s&clientSecret=%s',
                    $this->getBaseUrl(),
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
        } catch (\Exception $e) {
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
            // Get auth token for API access
            $authToken = $this->getAuthToken();
            if (! $authToken) {
                return PaymentResult::failed(
                    errorCode: 'AUTH_FAILED',
                    errorMessage: 'Failed to authenticate with Paymob',
                    errorMessageAr: __('payments.paymob.auth_check_failed'),
                    transactionId: $transactionId,
                );
            }

            // Get transaction details from Paymob
            $response = $this->request('GET', "/api/acceptance/transactions/{$transactionId}", [], [
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

                // Check if card was tokenized
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
        } catch (\Exception $e) {
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

    // ========================================
    // Tokenization (SupportsTokenization)
    // ========================================

    /**
     * Check if tokenization is properly configured.
     */
    public function supportsTokenization(): bool
    {
        $tokenizationEnabled = $this->config['tokenization']['enabled'] ?? true;
        $hasApiKey = ! empty($this->config['api_key']);

        return $tokenizationEnabled && $hasApiKey && $this->isConfigured();
    }

    /**
     * Get tokenization iframe URL for adding a card without payment.
     *
     * This creates a payment intention with save_card enabled and returns
     * the unified checkout URL where the user can enter card details.
     */
    public function getTokenizationIframeUrl(int $userId, array $options = []): array
    {
        try {
            // Build billing data
            $billingData = [
                'first_name' => $options['first_name'] ?? 'User',
                'last_name' => $options['last_name'] ?? (string) $userId,
                'email' => $options['email'] ?? 'user@itqanway.com',
                'phone_number' => $options['phone'] ?? '+201000000000',
                'country' => $options['country'] ?? 'EGY',
                'city' => $options['city'] ?? 'Cairo',
                'street' => $options['street'] ?? 'NA',
                'building' => $options['building'] ?? 'NA',
                'floor' => $options['floor'] ?? 'NA',
                'apartment' => $options['apartment'] ?? 'NA',
            ];

            // Create a minimal intention for tokenization
            // Using 1 EGP (100 piasters) - this won't be charged
            $requestBody = [
                'amount' => 100, // Minimal amount for card verification
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
                'save_card' => true, // Enable tokenization
                'special_reference' => sprintf('TOKEN-%d-%d', $userId, time()),
            ];

            // Add callback URL if provided
            if (! empty($options['callback_url'])) {
                $requestBody['redirection_url'] = $options['callback_url'];
            }

            // Make API request using Unified Intention API
            $response = $this->request('POST', '/v1/intention/', $requestBody, [
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

            // Build unified checkout URL
            $iframeUrl = sprintf(
                '%s/unifiedcheckout/?publicKey=%s&clientSecret=%s',
                $this->getBaseUrl(),
                $this->config['public_key'],
                $clientSecret
            );

            return [
                'success' => true,
                'iframe_url' => $iframeUrl,
                'client_secret' => $clientSecret,
                'intention_id' => $data['id'] ?? null,
            ];
        } catch (\Exception $e) {
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
     *
     * Note: Paymob typically tokenizes during payment via the save_card flag.
     * This method is for explicit tokenization without a payment.
     */
    public function tokenizeCard(array $cardData, int $userId): TokenizationResult
    {
        try {
            // First, get an auth token using the API key
            $authToken = $this->getAuthToken();
            if (! $authToken) {
                return TokenizationResult::failed(
                    errorCode: 'AUTH_FAILED',
                    errorMessage: 'Failed to authenticate with Paymob',
                    errorMessageAr: __('payments.paymob.auth_failed'),
                );
            }

            // Paymob tokenization requires creating an order first
            // Register order
            $orderResponse = $this->request('POST', '/api/ecommerce/orders', [
                'auth_token' => $authToken,
                'delivery_needed' => false,
                'amount_cents' => 100, // Minimal amount for verification
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

            // Get payment key for tokenization
            $paymentKeyResponse = $this->request('POST', '/api/acceptance/payment_keys', [
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

            // Tokenize the card
            $tokenResponse = $this->request('POST', '/api/acceptance/tokens', [
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
        } catch (\Exception $e) {
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
            // Get auth token
            $authToken = $this->getAuthToken();
            if (! $authToken) {
                return PaymentResult::failed(
                    errorCode: 'AUTH_FAILED',
                    errorMessage: 'Failed to authenticate with Paymob',
                    errorMessageAr: __('payments.paymob.auth_failed'),
                );
            }

            // Create order
            $merchantOrderId = sprintf(
                '%d-%d-%d',
                $metadata['academy_id'] ?? 0,
                $metadata['payment_id'] ?? 0,
                time()
            );

            $orderResponse = $this->request('POST', '/api/ecommerce/orders', [
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

            // Build billing data
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

            // Get payment key
            $paymentKeyResponse = $this->request('POST', '/api/acceptance/payment_keys', [
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

            // Pay with token
            $payResponse = $this->request('POST', '/api/acceptance/payments/pay', [
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
        } catch (\Exception $e) {
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
        // Paymob doesn't have a direct token deletion API
        // Tokens expire naturally or are invalidated when card expires
        // Return true as the token will no longer be used
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
        // Paymob doesn't provide a token details endpoint
        // The details are returned during tokenization and should be stored locally
        return null;
    }

    // ========================================
    // Recurring Payments (SupportsRecurringPayments)
    // ========================================

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
        return 0; // No restriction
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

        // Merge user info from payment method
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

        // Update last used timestamp on success
        if ($result->isSuccessful()) {
            $paymentMethod->touchLastUsed();
        }

        return $result;
    }

    // ========================================
    // Refunds (SupportsRefunds)
    // ========================================

    /**
     * Process a refund.
     */
    public function refund(string $transactionId, ?int $amountInCents = null, ?string $reason = null): PaymentResult
    {
        try {
            $requestBody = [
                'transaction_id' => $transactionId,
            ];

            if ($amountInCents !== null) {
                $requestBody['amount_cents'] = $amountInCents;
            }

            $response = $this->request('POST', '/api/acceptance/void_refund/refund', $requestBody, [
                'Authorization' => 'Token '.$this->config['secret_key'],
            ]);

            if ($response['success'] && ($response['data']['success'] ?? false)) {
                $data = $response['data'];

                return PaymentResult::success(
                    transactionId: (string) ($data['id'] ?? $transactionId),
                    rawResponse: $data,
                    metadata: [
                        'refund_id' => $data['id'] ?? null,
                        'refunded_amount_cents' => $data['amount_cents'] ?? $amountInCents,
                    ],
                );
            }

            return PaymentResult::failed(
                errorCode: 'REFUND_FAILED',
                errorMessage: $response['data']['message'] ?? 'Refund failed',
                errorMessageAr: __('payments.paymob.refund_failed'),
                transactionId: $transactionId,
                rawResponse: $response['data'] ?? [],
            );
        } catch (\Exception $e) {
            Log::channel('payments')->error('Paymob refund exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.paymob.refund_error'),
                transactionId: $transactionId,
            );
        }
    }

    /**
     * Check if partial refunds are supported.
     */
    public function supportsPartialRefunds(): bool
    {
        return true;
    }

    /**
     * Get refund window in days.
     */
    public function getRefundWindow(): ?int
    {
        return 180; // 6 months
    }

    // ========================================
    // Void (SupportsVoid)
    // ========================================

    /**
     * Check if void operations are supported.
     */
    public function supportsVoid(): bool
    {
        return true;
    }

    /**
     * Get the time window for voiding transactions.
     */
    public function getVoidWindow(): ?int
    {
        return 24; // 24 hours (same-day settlement)
    }

    /**
     * Void an authorized/pending transaction.
     */
    public function void(string $transactionId, ?string $reason = null): PaymentResult
    {
        try {
            $response = $this->request('POST', '/api/acceptance/void_refund/void', [
                'transaction_id' => $transactionId,
            ], [
                'Authorization' => 'Token '.$this->config['secret_key'],
            ]);

            if ($response['success'] && ($response['data']['success'] ?? false)) {
                $data = $response['data'];

                return PaymentResult::success(
                    transactionId: (string) ($data['id'] ?? $transactionId),
                    rawResponse: $data,
                    metadata: [
                        'void_id' => $data['id'] ?? null,
                        'voided_amount_cents' => $data['amount_cents'] ?? null,
                    ],
                );
            }

            return PaymentResult::failed(
                errorCode: 'VOID_FAILED',
                errorMessage: $response['data']['message'] ?? 'Void failed',
                errorMessageAr: __('payments.paymob.void_failed'),
                transactionId: $transactionId,
                rawResponse: $response['data'] ?? [],
            );
        } catch (\Exception $e) {
            Log::channel('payments')->error('Paymob void exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.paymob.void_error'),
                transactionId: $transactionId,
            );
        }
    }

    /**
     * Check if a specific transaction can be voided.
     */
    public function canVoid(string $transactionId): bool
    {
        try {
            $result = $this->verifyPayment($transactionId);

            if (! $result->isSuccessful()) {
                return false;
            }

            $txn = $result->rawResponse;

            // Can only void if not already voided or refunded
            if ($txn['is_voided'] ?? false) {
                return false;
            }
            if ($txn['is_refunded'] ?? false) {
                return false;
            }

            // Check if within void window (24 hours)
            if (isset($txn['created_at'])) {
                $createdAt = \Carbon\Carbon::parse($txn['created_at']);
                $hoursSinceCreation = now()->diffInHours($createdAt);

                if ($hoursSinceCreation > 24) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ========================================
    // Transaction Inquiry
    // ========================================

    /**
     * Get detailed transaction information.
     */
    public function inquire(string $transactionId): PaymentResult
    {
        return $this->verifyPayment($transactionId);
    }

    /**
     * Get transaction by order ID.
     */
    public function getTransactionByOrderId(string $orderId): ?array
    {
        try {
            $authToken = $this->getAuthToken();
            if (! $authToken) {
                return null;
            }

            $response = $this->request('GET', "/api/ecommerce/orders/{$orderId}", [], [
                'Authorization' => "Bearer {$authToken}",
            ]);

            if ($response['success']) {
                return $response['data'];
            }

            return null;
        } catch (\Exception $e) {
            Log::channel('payments')->error('Paymob order inquiry exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ========================================
    // Webhooks (SupportsWebhooks)
    // ========================================

    /**
     * Verify webhook signature using HMAC.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $hmacSecret = $this->getWebhookSecret();
        if (empty($hmacSecret)) {
            Log::channel('payments')->warning('Paymob HMAC secret not configured');

            return false;
        }

        $receivedHmac = $request->query('hmac') ?? $request->header('Hmac');
        if (empty($receivedHmac)) {
            Log::channel('payments')->warning('No HMAC received in webhook');

            return false;
        }

        // Get the transaction object
        $data = $request->all();
        $obj = $data['obj'] ?? $data;

        // Build HMAC string in specific order (Paymob's order)
        $hmacString = $this->buildHmacString($obj);

        // Calculate HMAC
        $calculatedHmac = hash_hmac('sha512', $hmacString, $hmacSecret);

        $isValid = hash_equals($calculatedHmac, $receivedHmac);

        if (! $isValid) {
            Log::channel('payments')->warning('Paymob HMAC verification failed', [
                'received_prefix' => substr($receivedHmac, 0, 16).'...',
                'calculated_prefix' => substr($calculatedHmac, 0, 16).'...',
            ]);
        }

        return $isValid;
    }

    /**
     * Parse webhook payload into standardized format.
     */
    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return WebhookPayload::fromPaymob($request->all());
    }

    /**
     * Get webhook secret for signature verification.
     */
    public function getWebhookSecret(): string
    {
        return $this->config['hmac_secret'] ?? '';
    }

    /**
     * Get supported webhook event types.
     */
    public function getSupportedWebhookEvents(): array
    {
        return ['TRANSACTION', 'REFUND', 'VOIDED', 'TOKEN'];
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * Get an authentication token using the API key.
     */
    private function getAuthToken(): ?string
    {
        $apiKey = $this->config['api_key'] ?? null;
        if (empty($apiKey)) {
            Log::channel('payments')->error('Paymob API key not configured');

            return null;
        }

        $response = $this->request('POST', '/api/auth/tokens', [
            'api_key' => $apiKey,
        ]);

        if ($response['success'] && ! empty($response['data']['token'])) {
            return $response['data']['token'];
        }

        Log::channel('payments')->error('Paymob auth failed', [
            'response' => $response,
        ]);

        return null;
    }

    /**
     * Build billing data from PaymentIntent.
     */
    private function buildBillingData(PaymentIntent $intent): array
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

        // Add customer info from intent
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
     * Build HMAC verification string in Paymob's required order.
     */
    private function buildHmacString(array $obj): string
    {
        // Paymob's specific field ordering for HMAC calculation
        $fields = [
            'amount_cents' => $obj['amount_cents'] ?? '',
            'created_at' => $obj['created_at'] ?? '',
            'currency' => $obj['currency'] ?? '',
            'error_occured' => $obj['error_occured'] ?? $obj['error_occurred'] ?? 'false',
            'has_parent_transaction' => $obj['has_parent_transaction'] ?? 'false',
            'id' => $obj['id'] ?? '',
            'integration_id' => $obj['integration_id'] ?? '',
            'is_3d_secure' => $obj['is_3d_secure'] ?? 'false',
            'is_auth' => $obj['is_auth'] ?? 'false',
            'is_capture' => $obj['is_capture'] ?? 'false',
            'is_refunded' => $obj['is_refunded'] ?? 'false',
            'is_standalone_payment' => $obj['is_standalone_payment'] ?? 'true',
            'is_voided' => $obj['is_voided'] ?? 'false',
            'order_id' => $obj['order']['id'] ?? $obj['order_id'] ?? '',
            'owner' => $obj['owner'] ?? '',
            'pending' => $obj['pending'] ?? 'false',
            'source_data_pan' => $obj['source_data']['pan'] ?? '',
            'source_data_sub_type' => $obj['source_data']['sub_type'] ?? '',
            'source_data_type' => $obj['source_data']['type'] ?? '',
            'success' => $obj['success'] ?? 'false',
        ];

        // Convert booleans to string
        $values = array_map(function ($value) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return (string) $value;
        }, $fields);

        return implode('', $values);
    }

    /**
     * Get integration IDs based on payment method.
     */
    private function getIntegrationIds(string $method): array
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
     * Detect card brand from masked PAN.
     */
    private function detectCardBrand(string $maskedPan): ?string
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
