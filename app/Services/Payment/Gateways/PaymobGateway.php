<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\Payment\SupportsRefunds;
use App\Contracts\Payment\SupportsWebhooks;
use App\Enums\PaymentFlowType;
use App\Enums\PaymentResultStatus;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\WebhookPayload;
use App\Services\Payment\Exceptions\PaymentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

/**
 * Paymob payment gateway implementation using Unified Intention API.
 *
 * @see https://docs.paymob.com/docs/intention-api
 */
class PaymobGateway extends AbstractGateway implements SupportsWebhooks, SupportsRefunds
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
        return 'بيموب';
    }

    /**
     * Get supported payment methods.
     */
    public function getSupportedMethods(): array
    {
        return ['card', 'wallet', 'bank_installments'];
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

    /**
     * Create a payment intent using Unified Intention API.
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        try {
            // Build items array
            $items = [];
            foreach ($intent->items as $item) {
                $items[] = [
                    'name' => $item['name'] ?? 'اشتراك',
                    'amount' => $item['amount'] ?? $intent->amountInCents,
                    'quantity' => $item['quantity'] ?? 1,
                ];
            }

            if (empty($items)) {
                $items[] = [
                    'name' => $intent->description ?? 'اشتراك',
                    'amount' => $intent->amountInCents,
                    'quantity' => 1,
                ];
            }

            // Get integration IDs for payment methods
            $paymentMethods = $this->getIntegrationIds($intent->paymentMethod);

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
                ],
                'special_reference' => $merchantOrderId,
            ];

            // Add URLs if provided
            if ($intent->successUrl) {
                $requestBody['redirection_url'] = $intent->successUrl;
            }
            if ($intent->webhookUrl) {
                $requestBody['notification_url'] = $intent->webhookUrl;
            }

            // Make API request
            $response = $this->request('POST', '/v1/intention/', $requestBody, [
                'Authorization' => 'Token ' . $this->config['secret_key'],
            ]);

            if (! $response['success']) {
                Log::channel('payments')->error('Paymob intention failed', [
                    'response' => $response,
                    'intent' => $intent->toArray(),
                ]);

                return PaymentResult::failed(
                    errorCode: 'INTENTION_FAILED',
                    errorMessage: $response['error'] ?? 'Failed to create payment intention',
                    errorMessageAr: 'فشل في إنشاء طلب الدفع',
                    rawResponse: $response['data'],
                );
            }

            $data = $response['data'];

            // Build iframe URL
            $clientSecret = $data['client_secret'] ?? null;
            $intentionId = $data['id'] ?? null;
            $paymentKeys = $data['payment_keys'] ?? [];

            // Get iframe URL
            $iframeId = $this->config['iframe_id'];
            $iframeUrl = null;

            if ($clientSecret && $iframeId) {
                $iframeUrl = sprintf(
                    '%s/unifiedcheckout/?publicKey=%s&clientSecret=%s',
                    $this->getBaseUrl(),
                    $this->config['public_key'],
                    $clientSecret
                );
            }

            return PaymentResult::pending(
                transactionId: (string) $intentionId,
                iframeUrl: $iframeUrl,
                clientSecret: $clientSecret,
                paymentKeys: $paymentKeys,
                rawResponse: $data,
                metadata: [
                    'intention_id' => $intentionId,
                    'merchant_order_id' => $merchantOrderId,
                ],
            );
        } catch (\Exception $e) {
            Log::channel('payments')->error('Paymob exception', [
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
     * Verify a payment after callback.
     */
    public function verifyPayment(string $transactionId, array $data = []): PaymentResult
    {
        try {
            // Get transaction details from Paymob
            $response = $this->request('GET', "/api/acceptance/transactions/{$transactionId}", [], [
                'Authorization' => 'Token ' . $this->config['secret_key'],
            ]);

            if (! $response['success']) {
                return PaymentResult::failed(
                    errorCode: 'VERIFICATION_FAILED',
                    errorMessage: 'Failed to verify payment',
                    errorMessageAr: 'فشل في التحقق من الدفع',
                    transactionId: $transactionId,
                    rawResponse: $response['data'],
                );
            }

            $txn = $response['data'];

            if ($txn['success'] === true) {
                return PaymentResult::success(
                    transactionId: $transactionId,
                    gatewayOrderId: (string) ($txn['order']['id'] ?? ''),
                    rawResponse: $txn,
                    metadata: [
                        'amount_cents' => $txn['amount_cents'],
                        'currency' => $txn['currency'],
                        'source_type' => $txn['source_data']['type'] ?? 'card',
                    ],
                );
            }

            return PaymentResult::failed(
                errorCode: $txn['data']['txn_response_code'] ?? 'DECLINED',
                errorMessage: $txn['data']['message'] ?? 'Payment was declined',
                errorMessageAr: 'تم رفض الدفع',
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
                errorMessageAr: 'حدث خطأ أثناء التحقق',
                transactionId: $transactionId,
            );
        }
    }

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
                'Authorization' => 'Token ' . $this->config['secret_key'],
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
                errorMessageAr: 'فشل في إجراء الاسترداد',
                transactionId: $transactionId,
                rawResponse: $response['data'],
            );
        } catch (\Exception $e) {
            Log::channel('payments')->error('Paymob refund exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: 'حدث خطأ أثناء الاسترداد',
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
                'received' => $receivedHmac,
                'calculated' => $calculatedHmac,
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
        return ['TRANSACTION', 'REFUND', 'VOIDED'];
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
            'all' => array_filter([
                (int) ($integrations['card'] ?? 0),
                (int) ($integrations['wallet'] ?? 0),
            ]),
            default => array_filter([(int) ($integrations['card'] ?? 0)]),
        };
    }
}
