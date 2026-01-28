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
     */
    public const PAYMENT_OPTION_FAWRY = 2;

    public const PAYMENT_OPTION_AMAN = 3;

    public const PAYMENT_OPTION_CARD = 4;

    public const PAYMENT_OPTION_WALLET = 5;

    public const PAYMENT_OPTION_MEEZA = 6;

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
            // Format: {academy_id}-{payment_id}-{timestamp}
            $customerReference = sprintf(
                '%d-%d-%d',
                $intent->academyId,
                $intent->paymentId ?? 0,
                time()
            );

            // Calculate amount in major units (EasyKash uses major units, not cents)
            $amount = $intent->amountInCents / 100;

            // Build request body for EasyKash Pay API
            $requestBody = [
                'amount' => $amount,
                'currency' => $intent->currency,
                'cashExpiry' => (int) ($this->config['cash_expiry_days'] ?? 3),
                'name' => $intent->customerName ?? 'Customer',
                'email' => $intent->customerEmail ?? 'customer@example.com',
                'mobile' => $this->formatPhoneNumber($intent->customerPhone),
                'redirectUrl' => $intent->successUrl ?? route('payments.callback', ['gateway' => 'easykash']),
                'customerReference' => $customerReference,
            ];

            // Add payment options if configured (set via EASYKASH_PAYMENT_OPTIONS env variable)
            $paymentOptions = $this->config['payment_options'] ?? null;
            if (! empty($paymentOptions)) {
                $requestBody['paymentOptions'] = $paymentOptions;
            }

            Log::info('EasyKash creating payment intent', [
                'customer_reference' => $customerReference,
                'amount' => $amount,
                'currency' => $intent->currency,
                'payment_options' => $paymentOptions,
            ]);

            // Make API request to EasyKash Direct Pay API
            $response = $this->request('POST', '/api/directpayv1/pay', $requestBody);

            if (! $response['success']) {
                Log::error('EasyKash payment creation failed', [
                    'response' => $response,
                    'intent' => $intent->toArray(),
                ]);

                return PaymentResult::failed(
                    errorCode: 'PAYMENT_CREATION_FAILED',
                    errorMessage: $response['error'] ?? 'Failed to create payment',
                    errorMessageAr: 'فشل في إنشاء طلب الدفع',
                    rawResponse: $response['data'] ?? [],
                );
            }

            $data = $response['data'];

            // EasyKash returns a redirectUrl that the user must visit
            $redirectUrl = $data['redirectUrl'] ?? null;

            if (empty($redirectUrl)) {
                Log::error('EasyKash no redirect URL returned', [
                    'response' => $data,
                ]);

                return PaymentResult::failed(
                    errorCode: 'NO_REDIRECT_URL',
                    errorMessage: 'EasyKash did not return a redirect URL',
                    errorMessageAr: 'لم يتم الحصول على رابط الدفع',
                    rawResponse: $data,
                );
            }

            return PaymentResult::pending(
                transactionId: $customerReference, // Use customerReference as transaction ID until we get easykashRef
                redirectUrl: $redirectUrl,
                rawResponse: $data,
                metadata: [
                    'customer_reference' => $customerReference,
                    'payment_id' => $intent->paymentId,
                    'academy_id' => $intent->academyId,
                ],
            );
        } catch (\Exception $e) {
            Log::error('EasyKash exception', [
                'message' => $e->getMessage(),
                'intent' => $intent->toArray(),
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
     * For redirect-based flow, default to ALL options so users can choose on EasyKash's page.
     *
     * @return array<int> Array of EasyKash payment option IDs
     */
    private function mapPaymentOptions(string $method): array
    {
        return match ($method) {
            'card' => [self::PAYMENT_OPTION_CARD],
            'wallet' => [self::PAYMENT_OPTION_WALLET],
            'fawry' => [self::PAYMENT_OPTION_FAWRY],
            'aman' => [self::PAYMENT_OPTION_AMAN],
            'meeza' => [self::PAYMENT_OPTION_MEEZA],
            // Default to ALL payment options for redirect-based flow
            // Users select their preferred method on EasyKash's hosted page
            default => [
                self::PAYMENT_OPTION_FAWRY,   // 2 - Fawry
                self::PAYMENT_OPTION_AMAN,    // 3 - Aman
                self::PAYMENT_OPTION_CARD,    // 4 - Credit/Debit Card
                self::PAYMENT_OPTION_WALLET,  // 5 - Mobile Wallet
                self::PAYMENT_OPTION_MEEZA,   // 6 - Meeza
            ],
        };
    }

    /**
     * Format phone number for EasyKash API.
     *
     * EasyKash expects Egyptian phone numbers.
     */
    private function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '01000000000';
        }

        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Egyptian phone numbers
        if (str_starts_with($phone, '2')) {
            $phone = substr($phone, 1); // Remove country code 2
        }

        if (str_starts_with($phone, '002')) {
            $phone = substr($phone, 3); // Remove country code 002
        }

        // Ensure it starts with 0
        if (! str_starts_with($phone, '0')) {
            $phone = '0'.$phone;
        }

        return $phone;
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
