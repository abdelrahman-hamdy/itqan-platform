<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\Payment\SupportsWebhooks;
use App\Enums\PaymentFlowType;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\WebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * EasyKash payment gateway implementation.
 *
 * Supports Direct Payment API with redirect flow.
 * Payment methods: Card, Wallet, Fawry, Aman, Meeza
 *
 * @see https://easykash.gitbook.io/easykash-apis-documentation
 */
class EasyKashGateway extends AbstractGateway implements SupportsWebhooks
{
    /**
     * EasyKash Payment Option IDs
     * @see https://easykash.gitbook.io/easykash-apis-documentation/direct-payment-hosted/pay-api
     */
    public const PAYMENT_OPTION_AMAN = 1;           // Cash through AMAN

    public const PAYMENT_OPTION_CARD = 2;           // Credit & Debit Card

    public const PAYMENT_OPTION_QASSATLY = 3;       // Qassatly

    public const PAYMENT_OPTION_WALLET = 4;         // Mobile Wallet

    public const PAYMENT_OPTION_FAWRY = 5;          // Cash Through Fawry

    public const PAYMENT_OPTION_MEEZA = 6;          // Meeza

    public const PAYMENT_OPTION_NBE_6M = 8;         // 6 Months - NBE installments

    public const PAYMENT_OPTION_NBE_12M = 9;        // 12 Months - NBE installments

    public const PAYMENT_OPTION_NBE_18M = 10;       // 18 Months - NBE installments

    public const PAYMENT_OPTION_VALU = 17;          // ValU

    public const PAYMENT_OPTION_BM_6M = 18;         // 6 months - Banque Misr installments

    public const PAYMENT_OPTION_BM_12M = 19;        // 12 months - Banque Misr installments

    public const PAYMENT_OPTION_BM_18M = 20;        // 18 months - Banque Misr installments

    public const PAYMENT_OPTION_AMAN_INSTALLMENTS = 21;  // Aman installments

    public const PAYMENT_OPTION_SOUHOULA = 22;      // Souhoula

    public const PAYMENT_OPTION_CONTACT = 23;       // Contact

    public const PAYMENT_OPTION_MOGO = 24;          // Mogo/MidTakseet

    public const PAYMENT_OPTION_BLNK = 25;          // Blnk

    public const PAYMENT_OPTION_MULTI_6M = 26;      // 6 months installments - Multiple Banks

    public const PAYMENT_OPTION_MULTI_12M = 27;     // 12 months installments - Multiple Banks

    public const PAYMENT_OPTION_MULTI_18M = 28;     // 18 months installments - Multiple Banks

    public const PAYMENT_OPTION_HALAN = 29;         // Halan

    public const PAYMENT_OPTION_APPLE_PAY = 31;     // Apple Pay

    public const PAYMENT_OPTION_TRU = 32;           // TRU

    public const PAYMENT_OPTION_KLIVVR = 33;        // Klivvr

    public const PAYMENT_OPTION_FORSA = 34;         // Forsa

    /**
     * Get the gateway identifier name.
     */
    public function getName(): string
    {
        return 'easykash';
    }

    /**
     * Get the human-readable display name (Arabic).
     */
    public function getDisplayName(): string
    {
        return 'إيزي كاش';
    }

    /**
     * Get supported payment methods.
     */
    public function getSupportedMethods(): array
    {
        return ['card', 'wallet', 'fawry', 'aman', 'meeza'];
    }

    /**
     * Get the payment flow type.
     */
    public function getFlowType(): PaymentFlowType
    {
        return PaymentFlowType::REDIRECT;
    }

    /**
     * Get required configuration keys.
     */
    protected function getRequiredConfigKeys(): array
    {
        return ['api_key', 'secret_key'];
    }

    /**
     * Get the base URL for EasyKash API.
     */
    public function getBaseUrl(): string
    {
        if ($this->isSandbox()) {
            return $this->config['sandbox_url'] ?? 'https://sandbox.easykash.net';
        }

        return $this->config['base_url'] ?? 'https://back.easykash.net';
    }

    /**
     * Get default headers for API requests.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $this->config['api_key'] ?? '',
        ];
    }

    /**
     * Create a payment intent using EasyKash Pay API.
     *
     * This creates a redirect URL that the user must visit to complete payment.
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        try {
            // Build customer reference for tracking
            // CRITICAL: EasyKash requires customerReference to be a UNIQUE NUMBER (integer)
            // We combine payment_id with timestamp to ensure uniqueness even if same payment_id is reused
            // Format: YYYYMMDDHHMMSS + payment_id (e.g., 2026012909450012 for payment 12 at 2026-01-29 09:45:00)
            $timestamp = (int) date('ymdHis'); // 12 digits
            $paymentId = (int) ($intent->paymentId ?? 0);
            $customerReference = (int) ($timestamp * 100 + ($paymentId % 100)); // Ensures unique reference

            // Calculate amount in major units (EasyKash uses major units, not cents)
            // Amount must be a number, not a string
            $amount = round((float) ($intent->amountInCents / 100), 2);

            // cashExpiry is in HOURS (not days) according to EasyKash docs
            // Default is 72 hours (3 days) if not configured
            $cashExpiryHours = (int) ($this->config['cash_expiry_hours'] ?? $this->config['cash_expiry_days'] ?? 72);

            // Validate required fields
            $customerEmail = $intent->customerEmail;
            if (empty($customerEmail)) {
                throw new \InvalidArgumentException('Customer email is required for EasyKash payments');
            }

            $customerName = $intent->customerName;
            if (empty($customerName)) {
                $customerName = 'Customer';
            }

            $customerPhone = $this->formatPhoneNumber($intent->customerPhone);

            // Build redirect URL - must be a valid URL
            $redirectUrl = $intent->successUrl;
            if (empty($redirectUrl)) {
                $redirectUrl = route('payments.easykash.callback');
            }

            // Build request body for EasyKash Pay API
            // All numeric fields must be actual numbers (int/float), not strings
            // EasyKash supports: EGP, SAR, USD, EUR, GBP, QAR, AED
            // Amount should be in the currency being sent (EasyKash converts to EGP at checkout)
            $requestBody = [
                'amount' => $amount,
                'currency' => strtoupper($intent->currency), // Use intent currency - SAR, USD, etc. supported
                'cashExpiry' => $cashExpiryHours,
                'name' => $customerName,
                'email' => $customerEmail,
                'mobile' => $customerPhone,
                'redirectUrl' => $redirectUrl,
                'customerReference' => $customerReference,
            ];

            // Payment options configuration
            // IMPORTANT: Do NOT send paymentOptions unless explicitly configured
            // EasyKash will show all payment methods enabled in your dashboard
            // Sending paymentOptions restricts to only those methods
            $paymentOptions = $this->config['payment_options'] ?? null;
            if (is_array($paymentOptions) && count($paymentOptions) > 0) {
                // Validate that options are integers
                $validOptions = array_filter($paymentOptions, fn ($opt) => is_int($opt) && $opt > 0);
                if (count($validOptions) > 0) {
                    $requestBody['paymentOptions'] = array_values($validOptions);
                }
            }
            // Do NOT add default paymentOptions - let EasyKash dashboard control available methods

            Log::info('EasyKash creating payment intent', [
                'customer_reference' => $customerReference,
                'payment_id' => $intent->paymentId,
                'academy_id' => $intent->academyId,
                'amount' => $amount,
                'currency' => $requestBody['currency'],
                'has_redirect_url' => ! empty($redirectUrl),
                'payment_options' => $requestBody['paymentOptions'] ?? 'using dashboard defaults',
                'request_body_keys' => array_keys($requestBody),
            ]);

            // Make API request to EasyKash Direct Pay API
            $response = $this->request('POST', '/api/directpayv1/pay', $requestBody);

            Log::info('EasyKash API response', [
                'success' => $response['success'],
                'status' => $response['status'] ?? null,
                'data' => $response['data'] ?? null,
                'error' => $response['error'] ?? null,
            ]);

            $data = $response['data'] ?? [];

            // EasyKash returns HTTP 200 even for errors, with error in response body
            // Check for errors in: $data['error'], $data['message'], or HTTP failure
            $hasError = ! $response['success']
                || isset($data['error'])
                || (isset($data['message']) && ! isset($data['redirectUrl']));

            if ($hasError) {
                $errorMessage = $data['error']
                    ?? $data['message']
                    ?? $response['error']
                    ?? 'Failed to create payment';

                Log::error('EasyKash payment creation failed', [
                    'error_message' => $errorMessage,
                    'http_success' => $response['success'] ?? false,
                    'intent' => $intent->toSafeLogArray(),
                ]);

                return PaymentResult::failed(
                    errorCode: 'PAYMENT_CREATION_FAILED',
                    errorMessage: $errorMessage,
                    errorMessageAr: 'فشل في إنشاء طلب الدفع: '.$errorMessage,
                    rawResponse: $data,
                );
            }

            // EasyKash returns a redirectUrl that the user must visit
            $easykashRedirectUrl = $data['redirectUrl'] ?? null;

            if (empty($easykashRedirectUrl)) {
                Log::error('EasyKash no redirect URL returned', [
                    'response' => $data,
                    'request_body' => $requestBody,
                ]);

                return PaymentResult::failed(
                    errorCode: 'NO_REDIRECT_URL',
                    errorMessage: 'EasyKash did not return a redirect URL',
                    errorMessageAr: 'لم يتم الحصول على رابط الدفع',
                    rawResponse: $data,
                );
            }

            Log::info('EasyKash payment intent created successfully', [
                'payment_id' => $intent->paymentId,
                'redirect_url' => $easykashRedirectUrl,
                'customer_reference' => $customerReference,
            ]);

            return PaymentResult::pending(
                transactionId: (string) $customerReference, // Use customerReference as transaction ID until we get easykashRef
                redirectUrl: $easykashRedirectUrl,
                rawResponse: $data,
                metadata: [
                    'customer_reference' => $customerReference,
                    'payment_id' => $intent->paymentId,
                    'academy_id' => $intent->academyId,
                ],
            );
        } catch (\InvalidArgumentException $e) {
            Log::error('EasyKash invalid argument', [
                'message' => $e->getMessage(),
                'payment_id' => $intent->paymentId ?? null,
            ]);

            return PaymentResult::failed(
                errorCode: 'INVALID_ARGUMENT',
                errorMessage: $e->getMessage(),
                errorMessageAr: 'بيانات غير صالحة: '.$e->getMessage(),
            );
        } catch (\Exception $e) {
            Log::error('EasyKash exception', [
                'message' => $e->getMessage(),
                'intent' => $intent->toSafeLogArray(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: 'حدث خطأ غير متوقع',
            );
        }
    }

    /**
     * Verify a payment using EasyKash Inquire API.
     */
    public function verifyPayment(string $transactionId, array $data = []): PaymentResult
    {
        try {
            // transactionId could be customerReference or easykashRef
            $customerReference = $data['customerReference'] ?? $transactionId;

            $requestBody = [
                'customerReference' => $customerReference,
            ];

            $response = $this->request('POST', '/api/cash-api/inquire', $requestBody);

            if (! $response['success']) {
                return PaymentResult::failed(
                    errorCode: 'VERIFICATION_FAILED',
                    errorMessage: 'Failed to verify payment',
                    errorMessageAr: 'فشل في التحقق من الدفع',
                    transactionId: $transactionId,
                    rawResponse: $response['data'] ?? [],
                );
            }

            $txn = $response['data'];
            $status = strtoupper($txn['status'] ?? 'UNKNOWN');

            if ($status === 'PAID' || $status === 'DELIVERED') {
                return PaymentResult::success(
                    transactionId: $txn['easykashRef'] ?? $transactionId,
                    gatewayOrderId: $txn['voucher'] ?? null,
                    rawResponse: $txn,
                    metadata: [
                        'amount' => $txn['Amount'] ?? 0,
                        'payment_method' => $txn['PaymentMethod'] ?? 'card',
                        'buyer_name' => $txn['BuyerName'] ?? '',
                        'buyer_email' => $txn['BuyerEmail'] ?? '',
                    ],
                );
            }

            if ($status === 'NEW' || $status === 'PENDING') {
                return PaymentResult::pending(
                    transactionId: $txn['easykashRef'] ?? $transactionId,
                    rawResponse: $txn,
                    metadata: [
                        'status' => $status,
                        'voucher' => $txn['voucher'] ?? null,
                    ],
                );
            }

            // EXPIRED, FAILED, CANCELED, etc.
            return PaymentResult::failed(
                errorCode: $status,
                errorMessage: "Payment status: {$status}",
                errorMessageAr: $this->translateStatus($status),
                transactionId: $txn['easykashRef'] ?? $transactionId,
                rawResponse: $txn,
            );
        } catch (\Exception $e) {
            Log::error('EasyKash verification exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: 'حدث خطأ أثناء التحقق',
                transactionId: $transactionId,
            );
        }
    }

    /**
     * Verify webhook signature using HMAC-SHA512.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $secretKey = $this->getWebhookSecret();
        if (empty($secretKey)) {
            Log::warning('EasyKash secret key not configured');

            return false;
        }

        $payload = $request->all();
        $receivedSignature = $payload['signatureHash'] ?? '';

        if (empty($receivedSignature)) {
            Log::warning('No signature hash received in EasyKash webhook');

            return false;
        }

        // Build HMAC string in EasyKash's specific order
        $hmacString = $this->buildHmacString($payload);

        // Calculate HMAC-SHA512
        $calculatedSignature = hash_hmac('sha512', $hmacString, $secretKey);

        $isValid = hash_equals($calculatedSignature, $receivedSignature);

        if (! $isValid) {
            Log::warning('EasyKash HMAC verification failed', [
                'received' => substr($receivedSignature, 0, 20).'...',
                'calculated' => substr($calculatedSignature, 0, 20).'...',
            ]);
        }

        return $isValid;
    }

    /**
     * Parse webhook payload into standardized format.
     */
    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return WebhookPayload::fromEasyKash($request->all());
    }

    /**
     * Get webhook secret for signature verification.
     */
    public function getWebhookSecret(): string
    {
        return $this->config['secret_key'] ?? '';
    }

    /**
     * Get supported webhook event types.
     */
    public function getSupportedWebhookEvents(): array
    {
        return ['PAID', 'FAILED', 'EXPIRED', 'DELIVERED', 'CANCELED', 'REFUNDED'];
    }

    /**
     * Build HMAC verification string in EasyKash's required order.
     *
     * Order: ProductCode, Amount, ProductType, PaymentMethod, status, easykashRef, customerReference
     */
    private function buildHmacString(array $payload): string
    {
        $fields = [
            $payload['ProductCode'] ?? '',
            $payload['Amount'] ?? '',
            $payload['ProductType'] ?? '',
            $payload['PaymentMethod'] ?? '',
            $payload['status'] ?? '',
            $payload['easykashRef'] ?? '',
            $payload['customerReference'] ?? '',
        ];

        return implode('', $fields);
    }

    /**
     * Map internal payment method to EasyKash payment options.
     *
     * For redirect-based flow, default to common options so users can choose on EasyKash's page.
     * Note: Payment options will ONLY appear if they are enabled in your EasyKash business account.
     *
     * @see https://easykash.gitbook.io/easykash-apis-documentation/direct-payment-hosted/pay-api
     * @return array<int> Array of EasyKash payment option IDs
     */
    private function mapPaymentOptions(string $method): array
    {
        return match ($method) {
            'card' => [self::PAYMENT_OPTION_CARD],           // 2
            'wallet' => [self::PAYMENT_OPTION_WALLET],       // 4
            'fawry' => [self::PAYMENT_OPTION_FAWRY],         // 5
            'aman' => [self::PAYMENT_OPTION_AMAN],           // 1
            'meeza' => [self::PAYMENT_OPTION_MEEZA],         // 6
            // Default to common payment options for redirect-based flow
            // Users select their preferred method on EasyKash's hosted page
            default => [
                self::PAYMENT_OPTION_AMAN,    // 1 - Cash through AMAN
                self::PAYMENT_OPTION_CARD,    // 2 - Credit/Debit Card
                self::PAYMENT_OPTION_WALLET,  // 4 - Mobile Wallet
                self::PAYMENT_OPTION_FAWRY,   // 5 - Cash Through Fawry
                self::PAYMENT_OPTION_MEEZA,   // 6 - Meeza
            ],
        };
    }

    /**
     * Format phone number for EasyKash API.
     *
     * EasyKash expects Egyptian phone numbers (11 digits starting with 01).
     * If phone is not Egyptian, use a valid default.
     */
    private function formatPhoneNumber(?string $phone): string
    {
        // Default valid Egyptian phone number
        $defaultPhone = '01000000000';

        if (empty($phone)) {
            return $defaultPhone;
        }

        // Remove any non-numeric characters and leading/trailing whitespace
        $phone = preg_replace('/[^0-9]/', '', trim($phone));

        // If empty after cleanup, use default
        if (empty($phone)) {
            return $defaultPhone;
        }

        // Remove international prefixes
        // Egyptian: +20, 0020, 20
        if (str_starts_with($phone, '20') && strlen($phone) > 10) {
            $phone = substr($phone, 2);
        }
        if (str_starts_with($phone, '0020') && strlen($phone) > 12) {
            $phone = substr($phone, 4);
        }

        // Saudi: +966, 00966, 966
        if (str_starts_with($phone, '966')) {
            // Saudi phone - use default Egyptian number since EasyKash is Egyptian gateway
            Log::debug('EasyKash: Saudi phone detected, using default Egyptian phone', [
                'original' => $phone,
            ]);
            return $defaultPhone;
        }

        // Other international codes - use default
        if (strlen($phone) > 11 && ! str_starts_with($phone, '0')) {
            Log::debug('EasyKash: International phone detected, using default Egyptian phone', [
                'original' => $phone,
                'length' => strlen($phone),
            ]);
            return $defaultPhone;
        }

        // Ensure it starts with 0 for Egyptian format
        if (! str_starts_with($phone, '0')) {
            $phone = '0'.$phone;
        }

        // Validate Egyptian mobile format: must be 11 digits starting with 01
        // Valid prefixes: 010, 011, 012, 015
        if (strlen($phone) === 11 && preg_match('/^01[0125]/', $phone)) {
            return $phone;
        }

        // Invalid format - use default
        Log::debug('EasyKash: Invalid phone format, using default', [
            'original' => $phone,
            'length' => strlen($phone),
        ]);

        return $defaultPhone;
    }

    /**
     * Translate EasyKash status to Arabic.
     */
    private function translateStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'PAID', 'DELIVERED' => 'تم الدفع بنجاح',
            'NEW', 'PENDING' => 'في انتظار الدفع',
            'EXPIRED' => 'انتهت صلاحية الدفع',
            'FAILED' => 'فشل الدفع',
            'CANCELED' => 'تم إلغاء الدفع',
            'REFUNDED' => 'تم استرداد المبلغ',
            default => 'حالة غير معروفة',
        };
    }
}
