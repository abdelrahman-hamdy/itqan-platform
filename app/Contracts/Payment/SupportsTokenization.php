<?php

namespace App\Contracts\Payment;

use App\Models\SavedPaymentMethod;
use App\Services\Payment\DTOs\PaymentResult;
use App\Services\Payment\DTOs\TokenizationResult;

/**
 * Interface for payment gateways that support card tokenization.
 *
 * Tokenization allows saving payment methods for future use without
 * storing sensitive card data. The gateway stores the card details
 * and returns a token that can be used for subsequent charges.
 */
interface SupportsTokenization
{
    /**
     * Tokenize a card and get a reusable token.
     *
     * This is used when a user explicitly wants to save a card
     * for future payments (e.g., from "manage payment methods" page).
     *
     * @param  array  $cardData  Card details (number, expiry, cvv, holder_name)
     * @param  int  $userId  The user ID who owns this card
     * @return TokenizationResult Result containing the token or error
     */
    public function tokenizeCard(array $cardData, int $userId): TokenizationResult;

    /**
     * Charge a tokenized card directly.
     *
     * This charges the card without requiring the user to re-enter
     * their card details. Used for recurring payments and quick checkout.
     *
     * @param  string  $token  The card token from tokenization
     * @param  int  $amountInCents  Amount to charge in cents/minor units
     * @param  string  $currency  ISO currency code (EGP, SAR, USD, etc.)
     * @param  array  $metadata  Additional data (payment_id, academy_id, etc.)
     * @return PaymentResult Payment result
     */
    public function chargeToken(string $token, int $amountInCents, string $currency, array $metadata = []): PaymentResult;

    /**
     * Delete a saved token from the gateway.
     *
     * This invalidates the token so it can no longer be used for charges.
     * Should be called when user removes a saved payment method.
     *
     * @param  string  $token  The token to delete
     * @return bool True if deletion was successful
     */
    public function deleteToken(string $token): bool;

    /**
     * Get details about a tokenized card.
     *
     * Returns information like last 4 digits, brand, expiry, etc.
     * Useful for displaying saved cards to the user.
     *
     * @param  string  $token  The token to query
     * @return array|null Card details or null if not found
     */
    public function getTokenDetails(string $token): ?array;

    /**
     * Check if the gateway supports tokenization with the current configuration.
     *
     * @return bool True if tokenization is properly configured
     */
    public function supportsTokenization(): bool;
}
