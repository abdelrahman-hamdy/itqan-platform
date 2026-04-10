<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\Payment\SupportsRefunds;
use App\Contracts\Payment\SupportsVoid;
use App\Contracts\Payment\SupportsWebhooks;
use App\Enums\PaymentFlowType;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\WebhookPayload;
use App\Services\Payment\Gateways\Paymob\PaymobApiClient;
use App\Services\Payment\Gateways\Paymob\PaymobPaymentProcessor;
use App\Services\Payment\Gateways\Paymob\PaymobRefundService;
use App\Services\Payment\Gateways\Paymob\PaymobWebhookProcessor;
use Illuminate\Http\Request;

/**
 * Paymob payment gateway implementation using Unified Intention API.
 *
 * This class acts as a thin delegate, routing each interface method to
 * the appropriate focused sub-service. No caller changes are required.
 *
 * Supports:
 * - Card payments (Visa, Mastercard, Meeza)
 * - Mobile wallets
 * - Apple Pay
 * - Bank installments
 * - Refunds and voids
 *
 * @see https://developers.paymob.com/egypt/
 */
class PaymobGateway extends AbstractGateway implements SupportsRefunds, SupportsVoid, SupportsWebhooks
{
    protected PaymobApiClient $apiClient;

    protected PaymobPaymentProcessor $paymentProcessor;

    protected PaymobRefundService $refundService;

    protected PaymobWebhookProcessor $webhookProcessor;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->apiClient = new PaymobApiClient($config);
        $this->paymentProcessor = new PaymobPaymentProcessor($this->apiClient, $config);
        $this->refundService = new PaymobRefundService($this->apiClient, $config);
        $this->webhookProcessor = new PaymobWebhookProcessor($config);
    }

    // ========================================
    // Identity / Metadata (kept inline – tiny)
    // ========================================

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
     * Baseline countries Paymob can process payments for.
     */
    public function getSupportedCountries(): array
    {
        return ['EG', 'SA', 'AE'];
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
        return $this->apiClient->getBaseUrl();
    }

    // ========================================
    // Payment Intent (Standard Payments)
    // ========================================

    /**
     * Create a payment intent using Unified Intention API.
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult
    {
        return $this->paymentProcessor->createPaymentIntent($intent);
    }

    /**
     * Verify a payment after callback.
     */
    public function verifyPayment(string $transactionId, array $data = []): PaymentResult
    {
        return $this->paymentProcessor->verifyPayment($transactionId, $data);
    }

    // ========================================
    // Refunds (SupportsRefunds)
    // ========================================

    /**
     * Process a refund.
     */
    public function refund(string $transactionId, ?int $amountInCents = null, ?string $reason = null): PaymentResult
    {
        return $this->refundService->refund($transactionId, $amountInCents, $reason);
    }

    /**
     * Check if partial refunds are supported.
     */
    public function supportsPartialRefunds(): bool
    {
        return $this->refundService->supportsPartialRefunds();
    }

    /**
     * Get refund window in days.
     */
    public function getRefundWindow(): ?int
    {
        return $this->refundService->getRefundWindow();
    }

    // ========================================
    // Void (SupportsVoid)
    // ========================================

    /**
     * Check if void operations are supported.
     */
    public function supportsVoid(): bool
    {
        return $this->refundService->supportsVoid();
    }

    /**
     * Get the time window for voiding transactions.
     */
    public function getVoidWindow(): ?int
    {
        return $this->refundService->getVoidWindow();
    }

    /**
     * Void an authorized/pending transaction.
     */
    public function void(string $transactionId, ?string $reason = null): PaymentResult
    {
        return $this->refundService->void($transactionId, $reason);
    }

    /**
     * Check if a specific transaction can be voided.
     */
    public function canVoid(string $transactionId): bool
    {
        return $this->refundService->canVoid(
            $transactionId,
            fn (string $id) => $this->verifyPayment($id)
        );
    }

    // ========================================
    // Transaction Inquiry
    // ========================================

    /**
     * Get detailed transaction information.
     */
    public function inquire(string $transactionId): PaymentResult
    {
        return $this->refundService->inquire(
            $transactionId,
            fn (string $id) => $this->verifyPayment($id)
        );
    }

    /**
     * Get transaction by order ID.
     */
    public function getTransactionByOrderId(string $orderId): ?array
    {
        return $this->refundService->getTransactionByOrderId($orderId);
    }

    // ========================================
    // Webhooks (SupportsWebhooks)
    // ========================================

    /**
     * Verify webhook signature using HMAC.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->webhookProcessor->verifyWebhookSignature($request);
    }

    /**
     * Parse webhook payload into standardized format.
     */
    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return $this->webhookProcessor->parseWebhookPayload($request);
    }

    /**
     * Get webhook secret for signature verification.
     */
    public function getWebhookSecret(): string
    {
        return $this->webhookProcessor->getWebhookSecret();
    }

    /**
     * Get supported webhook event types.
     */
    public function getSupportedWebhookEvents(): array
    {
        return $this->webhookProcessor->getSupportedWebhookEvents();
    }
}
