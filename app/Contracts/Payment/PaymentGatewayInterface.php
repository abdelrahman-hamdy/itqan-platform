<?php

namespace App\Contracts\Payment;

use App\Enums\PaymentFlowType;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;

/**
 * Core interface for payment gateway implementations.
 *
 * All payment gateways must implement this interface to ensure
 * consistent behavior across different payment providers.
 */
interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier name.
     */
    public function getName(): string;

    /**
     * Get the human-readable display name (Arabic).
     */
    public function getDisplayName(): string;

    /**
     * Check if the gateway is properly configured.
     */
    public function isConfigured(): bool;

    /**
     * Get supported payment methods for this gateway.
     *
     * @return array<string> e.g., ['card', 'wallet', 'bank_transfer']
     */
    public function getSupportedMethods(): array;

    /**
     * Get the payment flow type for this gateway.
     */
    public function getFlowType(): PaymentFlowType;

    /**
     * Create a payment intent/session with the gateway.
     *
     * This initiates the payment process and returns the necessary
     * data for the client to complete the payment.
     */
    public function createPaymentIntent(PaymentIntent $intent): PaymentResult;

    /**
     * Verify a payment after completion/callback.
     *
     * @param  string  $transactionId  The gateway's transaction reference
     * @param  array  $data  Additional verification data (e.g., from callback)
     */
    public function verifyPayment(string $transactionId, array $data = []): PaymentResult;

    /**
     * Get the base URL for the gateway API.
     */
    public function getBaseUrl(): string;

    /**
     * Check if running in sandbox/test mode.
     */
    public function isSandbox(): bool;
}
