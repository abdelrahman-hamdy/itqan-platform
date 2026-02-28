<?php

namespace App\Services\Payment\Gateways;

use Exception;
use App\Contracts\Payment\SupportsWebhooks;
use App\Enums\PaymentFlowType;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\WebhookPayload;
use App\Services\Payment\TapSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Tap payment gateway implementation.
 *
 * Supports GCC payment methods via redirect flow.
 * Payment methods: Card, Mada, Apple Pay, Google Pay
 *
 * @see https://developers.tap.company/docs
 */
class TapGateway extends AbstractGateway implements SupportsWebhooks
{
    /**
     * Tap charge status constants.
     */
    public const STATUS_INITIATED = 'INITIATED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_CAPTURED = 'CAPTURED';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_DECLINED = 'DECLINED';

    public const STATUS_RESTRICTED = 'RESTRICTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_REFUNDED = 'REFUNDED';

    public const STATUS_VOID = 'VOID';

    /**
     * Get the gateway identifier name.
     */
    public function getName(): string
    {
        return 'tap';
    }

    /**
     * Get the human-readable display name.
     */
    public function getDisplayName(): string
    {
        return __('payments.gateways.tap');
    }

    /**
     * Get supported payment methods.
     */
    public function getSupportedMethods(): array
    {
        return ['card', 'mada', 'apple_pay', 'google_pay'];
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
        return ['secret_key', 'public_key'];
    }

    /**
     * Get default headers — Tap uses Bearer token auth.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.($this->config['secret_key'] ?? ''),
        ];
    }

    /**
     * Create a payment intent using the Tap Charges API.
     *
     * Returns a redirect URL that the user must visit to complete payment.
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        try {
            // Parse customer name parts
            $nameParts = explode(' ', $intent->customerName ?? 'Customer', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Parse phone: Tap expects country_code and number separately
            [$countryCode, $phoneNumber] = $this->parsePhone($intent->customerPhone);

            // Build the Tap charge body
            $chargeBody = [
                'amount' => round($intent->amountInCents / 100, 2),
                'currency' => strtoupper($intent->currency),
                'customer_initiated' => true,
                'threeDSecure' => true,
                'save_card' => false,
                'description' => $intent->description ?? __('payments.tap.charge_description'),
                'metadata' => [
                    'payment_id' => (string) ($intent->paymentId ?? ''),
                    'academy_id' => (string) ($intent->academyId ?? ''),
                ],
                'reference' => [
                    'transaction' => 'PAYMENT-'.($intent->paymentId ?? '0'),
                    'order' => 'ORDER-'.($intent->academyId ?? '0').'-'.($intent->paymentId ?? '0'),
                ],
                'receipt' => [
                    'email' => true,
                    'sms' => false,
                ],
                'customer' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $intent->customerEmail ?? '',
                    'phone' => [
                        'country_code' => $countryCode,
                        'number' => $phoneNumber,
                    ],
                ],
                'source' => [
                    'id' => 'src_all',  // Show all payment methods
                ],
                'post' => [
                    'url' => $intent->webhookUrl ?? '',  // Tap posts webhook here
                ],
                'redirect' => [
                    'url' => $intent->successUrl ?? '',  // Tap appends ?tap_id={charge_id} to this URL
                ],
            ];

            Log::info('Tap creating payment intent', [
                'payment_id' => $intent->paymentId,
                'academy_id' => $intent->academyId,
                'amount' => $chargeBody['amount'],
                'currency' => $chargeBody['currency'],
            ]);

            // POST to /charges endpoint
            $response = $this->request('POST', 'charges', $chargeBody);

            Log::info('Tap API response', [
                'success' => $response['success'],
                'status' => $response['status'] ?? null,
                'charge_id' => $response['data']['id'] ?? null,
            ]);

            if (! $response['success']) {
                $data = $response['data'] ?? [];
                $errorMessage = $data['errors'][0]['description'] ?? $data['message'] ?? $response['error'] ?? 'Failed to create payment';

                Log::error('Tap payment creation failed', [
                    'error_message' => $errorMessage,
                    'response' => $data,
                    'intent' => $intent->toSafeLogArray(),
                ]);

                return PaymentResult::failed(
                    errorCode: 'PAYMENT_CREATION_FAILED',
                    errorMessage: $errorMessage,
                    errorMessageAr: __('payments.tap.create_failed', ['error' => $errorMessage]),
                    rawResponse: $data,
                );
            }

            $data = $response['data'];
            $chargeId = $data['id'] ?? null;
            $redirectUrl = $data['transaction']['url'] ?? null;

            if (empty($chargeId) || empty($redirectUrl)) {
                Log::error('Tap no redirect URL returned', [
                    'response' => $data,
                ]);

                return PaymentResult::failed(
                    errorCode: 'NO_REDIRECT_URL',
                    errorMessage: 'Tap did not return a redirect URL',
                    errorMessageAr: __('payments.tap.no_payment_url'),
                    rawResponse: $data,
                );
            }

            Log::info('Tap payment intent created successfully', [
                'payment_id' => $intent->paymentId,
                'charge_id' => $chargeId,
                'redirect_url' => $redirectUrl,
            ]);

            return PaymentResult::pending(
                transactionId: $chargeId,
                redirectUrl: $redirectUrl,
                rawResponse: $data,
                metadata: [
                    'charge_id' => $chargeId,
                    'payment_id' => $intent->paymentId,
                    'academy_id' => $intent->academyId,
                ],
            );
        } catch (Exception $e) {
            Log::error('Tap exception during payment creation', [
                'message' => $e->getMessage(),
                'payment_id' => $intent->paymentId ?? null,
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.tap.unexpected_error'),
            );
        }
    }

    /**
     * Verify a payment by fetching the charge from Tap API.
     */
    public function verifyPayment(string $transactionId, array $data = []): PaymentResult
    {
        try {
            $response = $this->request('GET', "charges/{$transactionId}");

            if (! $response['success']) {
                return PaymentResult::failed(
                    errorCode: 'VERIFICATION_FAILED',
                    errorMessage: 'Failed to verify payment with Tap',
                    errorMessageAr: __('payments.tap.verify_failed'),
                    transactionId: $transactionId,
                    rawResponse: $response['data'] ?? [],
                );
            }

            $charge = $response['data'];
            $tapStatus = strtoupper($charge['status'] ?? 'UNKNOWN');

            return match ($tapStatus) {
                self::STATUS_CAPTURED => PaymentResult::success(
                    transactionId: $charge['id'],
                    gatewayOrderId: $charge['reference']['order'] ?? null,
                    rawResponse: $charge,
                    metadata: [
                        'payment_method' => $charge['source']['payment_method'] ?? 'card',
                        'card_brand' => $charge['card']['scheme'] ?? null,
                        'card_last_four' => $charge['card']['last_four'] ?? null,
                    ],
                ),
                self::STATUS_INITIATED, self::STATUS_IN_PROGRESS => PaymentResult::pending(
                    transactionId: $charge['id'],
                    rawResponse: $charge,
                    metadata: ['status' => $tapStatus],
                ),
                default => PaymentResult::failed(
                    errorCode: $tapStatus,
                    errorMessage: "Tap charge status: {$tapStatus}",
                    errorMessageAr: $this->translateStatus($tapStatus),
                    transactionId: $charge['id'] ?? $transactionId,
                    rawResponse: $charge,
                ),
            };
        } catch (Exception $e) {
            Log::error('Tap verification exception', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            return PaymentResult::failed(
                errorCode: 'EXCEPTION',
                errorMessage: $e->getMessage(),
                errorMessageAr: __('payments.tap.verify_error'),
                transactionId: $transactionId,
            );
        }
    }

    /**
     * Verify webhook signature via TapSignatureService.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        return app(TapSignatureService::class)->verify($request);
    }

    /**
     * Parse webhook payload into standardized format.
     */
    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return WebhookPayload::fromTap($request->all());
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
        return [self::STATUS_CAPTURED, self::STATUS_FAILED, self::STATUS_DECLINED, self::STATUS_CANCELLED, self::STATUS_REFUNDED];
    }

    /**
     * Parse phone number into [country_code, number] suitable for Tap API.
     *
     * @return array{string, string}
     */
    private function parsePhone(?string $phone): array
    {
        $defaultCountryCode = '966';
        $defaultNumber = '500000000';

        if (empty($phone)) {
            return [$defaultCountryCode, $defaultNumber];
        }

        // Remove non-numeric chars
        $phone = preg_replace('/[^0-9]/', '', trim($phone));

        if (empty($phone)) {
            return [$defaultCountryCode, $defaultNumber];
        }

        // Saudi: +966 → 9665XXXXXXXX
        if (str_starts_with($phone, '966') && strlen($phone) >= 12) {
            return ['966', substr($phone, 3)];
        }

        // Egypt: +20 → 201XXXXXXXXX
        if (str_starts_with($phone, '20') && strlen($phone) >= 11) {
            return ['20', substr($phone, 2)];
        }

        // Kuwait: +965
        if (str_starts_with($phone, '965') && strlen($phone) >= 11) {
            return ['965', substr($phone, 3)];
        }

        // UAE: +971
        if (str_starts_with($phone, '971') && strlen($phone) >= 11) {
            return ['971', substr($phone, 3)];
        }

        // Fallback: use as-is with Saudi country code
        if (str_starts_with($phone, '05') || str_starts_with($phone, '5')) {
            $number = ltrim($phone, '0');

            return ['966', $number];
        }

        return [$defaultCountryCode, $defaultNumber];
    }

    /**
     * Translate Tap status to Arabic.
     */
    private function translateStatus(string $status): string
    {
        return match (strtoupper($status)) {
            self::STATUS_CAPTURED => __('payments.status_display.paid'),
            self::STATUS_INITIATED, self::STATUS_IN_PROGRESS => __('payments.status_display.pending'),
            self::STATUS_FAILED => __('payments.status_display.failed'),
            self::STATUS_DECLINED => __('payments.status_display.failed'),
            self::STATUS_CANCELLED => __('payments.status_display.canceled'),
            self::STATUS_REFUNDED => __('payments.status_display.refunded'),
            default => __('payments.status_display.unknown'),
        };
    }
}
