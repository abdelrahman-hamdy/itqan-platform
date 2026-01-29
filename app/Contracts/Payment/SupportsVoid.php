<?php

namespace App\Contracts\Payment;

use App\Services\Payment\DTOs\PaymentResult;

/**
 * Interface for payment gateways that support voiding transactions.
 *
 * Voiding is different from refunding:
 * - Void: Cancels an authorized but not settled transaction (no fees)
 * - Refund: Returns money from a settled/captured transaction (may have fees)
 *
 * Voiding is typically only available for a limited time window
 * after the authorization (usually same-day or until settlement).
 */
interface SupportsVoid
{
    /**
     * Void an authorized/pending transaction.
     *
     * This cancels the transaction before it settles, avoiding
     * any processing fees that would apply to a refund.
     *
     * @param  string  $transactionId  The transaction ID to void
     * @param  string|null  $reason  Optional reason for voiding
     * @return PaymentResult Result indicating success or failure
     */
    public function void(string $transactionId, ?string $reason = null): PaymentResult;

    /**
     * Check if the gateway supports void operations.
     *
     * @return bool True if void is supported
     */
    public function supportsVoid(): bool;

    /**
     * Get the time window for voiding transactions.
     *
     * Returns the number of hours after authorization during which
     * a transaction can be voided. After this window, only refunds
     * are possible.
     *
     * @return int|null Hours until void window closes, null if no limit
     */
    public function getVoidWindow(): ?int;

    /**
     * Check if a specific transaction can be voided.
     *
     * This checks both the gateway capability and the transaction's
     * current state (e.g., not yet settled, within void window).
     *
     * @param  string  $transactionId  The transaction to check
     * @return bool True if the transaction can be voided
     */
    public function canVoid(string $transactionId): bool;
}
