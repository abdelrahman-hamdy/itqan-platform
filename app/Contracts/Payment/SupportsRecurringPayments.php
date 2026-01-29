<?php

namespace App\Contracts\Payment;

use App\Models\SavedPaymentMethod;
use App\Services\Payment\DTOs\PaymentResult;

/**
 * Interface for payment gateways that support recurring/automatic payments.
 *
 * This interface extends tokenization capabilities to support automatic
 * subscription billing using saved payment methods.
 */
interface SupportsRecurringPayments
{
    /**
     * Charge a saved payment method for recurring billing.
     *
     * This is the primary method used for subscription auto-renewal.
     * It uses the SavedPaymentMethod model which contains the token
     * and additional metadata needed for the charge.
     *
     * @param  SavedPaymentMethod  $paymentMethod  The saved payment method to charge
     * @param  int  $amountInCents  Amount to charge in cents/minor units
     * @param  string  $currency  ISO currency code (EGP, SAR, USD, etc.)
     * @param  array  $metadata  Additional data (subscription_id, payment_id, etc.)
     * @return PaymentResult Payment result
     */
    public function chargeSavedPaymentMethod(
        SavedPaymentMethod $paymentMethod,
        int $amountInCents,
        string $currency,
        array $metadata = []
    ): PaymentResult;

    /**
     * Check if the gateway supports recurring payments.
     *
     * A gateway may support tokenization but not recurring payments
     * if, for example, their API doesn't allow merchant-initiated charges.
     *
     * @return bool True if recurring payments are supported
     */
    public function supportsRecurring(): bool;

    /**
     * Get the minimum interval between recurring charges in days.
     *
     * Some gateways may have restrictions on how frequently
     * a saved card can be charged.
     *
     * @return int Minimum days between charges (0 = no restriction)
     */
    public function getMinimumRecurringInterval(): int;
}
