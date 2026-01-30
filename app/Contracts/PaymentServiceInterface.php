<?php

namespace App\Contracts;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Services\Payment\DTOs\PaymentResult;

/**
 * PaymentServiceInterface
 *
 * Interface for the main payment orchestration service.
 * This service coordinates payment processing across different gateway implementations.
 */
interface PaymentServiceInterface
{
    /**
     * Process payment with the appropriate gateway.
     *
     * This is the main entry point for initiating payments.
     *
     * @param  Payment  $payment  The payment record to process
     * @param  array  $paymentData  Additional payment data (success_url, cancel_url, webhook_url, etc.)
     * @return array Response with success status and payment details or error information
     */
    public function processPayment(Payment $payment, array $paymentData = []): array;

    /**
     * Process subscription renewal payment.
     *
     * Accepts either:
     * 1. A Payment object - processes the existing payment record
     * 2. A BaseSubscription + renewal price - creates payment and charges saved card
     *
     * Called by HandlesSubscriptionRenewal trait for automatic renewals.
     *
     * @param  Payment|BaseSubscription  $paymentOrSubscription  The payment or subscription to renew
     * @param  float|null  $renewalPrice  The renewal amount (required if passing a subscription)
     * @return array Response with success status and payment details or error information
     */
    public function processSubscriptionRenewal(
        Payment|BaseSubscription $paymentOrSubscription,
        ?float $renewalPrice = null
    ): array;

    /**
     * Verify a payment with the gateway.
     *
     * @param  Payment  $payment  The payment to verify
     * @param  array  $data  Additional verification data from gateway callback
     * @return PaymentResult The verification result
     */
    public function verifyPayment(Payment $payment, array $data = []): PaymentResult;

    /**
     * Process a refund for a payment.
     *
     * @param  Payment  $payment  The payment to refund
     * @param  int|null  $amountInCents  The amount to refund in cents (null for full refund)
     * @param  string|null  $reason  The reason for the refund
     * @return PaymentResult The refund result
     */
    public function refund(Payment $payment, ?int $amountInCents = null, ?string $reason = null): PaymentResult;

    /**
     * Get available payment methods for an academy.
     *
     * @param  mixed|null  $academy  The academy to get payment methods for
     * @return array Available payment methods with their details
     */
    public function getAvailablePaymentMethods($academy = null): array;

    /**
     * Calculate fees for a payment method.
     *
     * @param  float  $amount  The payment amount
     * @param  string  $paymentMethod  The payment method (card, wallet, bank_transfer, etc.)
     * @return array Fee details including fee_rate, fee_amount, and total_with_fees
     */
    public function calculateFees(float $amount, string $paymentMethod): array;

    /**
     * Get a specific gateway instance.
     *
     * @param  string|null  $name  The gateway name (null for default gateway)
     * @return PaymentGatewayInterface The gateway instance
     */
    public function gateway(?string $name = null): PaymentGatewayInterface;
}
