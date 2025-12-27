<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\DTOs\PaymentResult;

/**
 * Interface for payment processing services.
 *
 * This interface defines the contract for processing payments across
 * different payment gateways (Paymob, Tap, etc.).
 */
interface PaymentProcessorInterface
{
    /**
     * Create a payment intent for a new payment.
     *
     * @param Payment $payment The payment model
     * @param array $options Additional options for the payment
     * @return PaymentIntent The created payment intent
     */
    public function createIntent(Payment $payment, array $options = []): PaymentIntent;

    /**
     * Process a payment using the configured gateway.
     *
     * @param Payment $payment The payment model
     * @param array $options Additional options for processing
     * @return array The result of the payment processing
     */
    public function processPayment(Payment $payment, array $options = []): array;

    /**
     * Process a subscription renewal payment.
     *
     * @param Payment $payment The payment model for renewal
     * @return array The result of the renewal processing
     */
    public function processSubscriptionRenewal(Payment $payment): array;

    /**
     * Verify a payment callback/webhook from the gateway.
     *
     * @param string $gateway The gateway name
     * @param array $payload The callback payload
     * @return array The verification result
     */
    public function verifyCallback(string $gateway, array $payload): array;

    /**
     * Request a refund for a payment.
     *
     * @param Payment $payment The payment to refund
     * @param float|null $amount Optional partial refund amount
     * @param string|null $reason The reason for refund
     * @return array The refund result
     */
    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null): array;

    /**
     * Get payment status from the gateway.
     *
     * @param Payment $payment The payment to check
     * @return array The current payment status
     */
    public function getStatus(Payment $payment): array;

    /**
     * Get available payment methods for a user.
     *
     * @param int $userId The user ID
     * @param int|null $academyId Optional academy ID for context
     * @return array List of available payment methods
     */
    public function getAvailablePaymentMethods(int $userId, ?int $academyId = null): array;
}
