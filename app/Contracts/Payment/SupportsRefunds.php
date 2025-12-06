<?php

namespace App\Contracts\Payment;

use App\Services\Payment\DTOs\PaymentResult;

/**
 * Interface for payment gateways that support refund operations.
 *
 * Gateways implementing this interface can process full or partial
 * refunds for completed transactions.
 */
interface SupportsRefunds
{
    /**
     * Process a refund for a transaction.
     *
     * @param string $transactionId The original transaction ID
     * @param int $amountInCents Amount to refund in cents (null for full refund)
     * @param string|null $reason Optional reason for the refund
     */
    public function refund(string $transactionId, ?int $amountInCents = null, ?string $reason = null): PaymentResult;

    /**
     * Check if partial refunds are supported.
     */
    public function supportsPartialRefunds(): bool;

    /**
     * Get the maximum refund window in days.
     *
     * Returns null if there's no limit.
     */
    public function getRefundWindow(): ?int;
}
