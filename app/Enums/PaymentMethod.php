<?php

namespace App\Enums;

/**
 * Payment Method Enum
 *
 * Defines all supported payment methods in the Itqan Platform.
 * Each method may have different fee structures and processing flows.
 *
 * Supported Gateways:
 * - Paymob: credit_card, debit_card, mada, wallet
 * - Tap Payments: credit_card, debit_card, apple_pay
 * - Manual: bank_transfer, cash
 *
 * @see \App\Models\Payment
 * @see \App\Services\PaymentService
 */
enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';        // Credit card (Visa, Mastercard)
    case DEBIT_CARD = 'debit_card';          // Debit card
    case BANK_TRANSFER = 'bank_transfer';    // Bank transfer (manual)
    case CASH = 'cash';                      // Cash payment
    case WALLET = 'wallet';                  // Digital wallet
    case PAYPAL = 'paypal';                  // PayPal
    case APPLE_PAY = 'apple_pay';            // Apple Pay
    case GOOGLE_PAY = 'google_pay';          // Google Pay
    case STC_PAY = 'stc_pay';                // STC Pay (Saudi)

    /**
     * Get the localized label for the payment method
     */
    public function label(): string
    {
        return __('enums.payment_method.'.$this->value);
    }

    /**
     * Get the icon for the payment method
     */
    public function icon(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'ri-bank-card-line',
            self::DEBIT_CARD => 'ri-bank-card-2-line',
            self::BANK_TRANSFER => 'ri-bank-line',
            self::CASH => 'ri-money-dollar-circle-line',
            self::WALLET => 'ri-wallet-3-line',
            self::PAYPAL => 'ri-paypal-line',
            self::APPLE_PAY => 'ri-apple-line',
            self::GOOGLE_PAY => 'ri-google-line',
            self::STC_PAY => 'ri-smartphone-line',
        };
    }

    /**
     * Get the Filament color class for the payment method
     */
    public function color(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'primary',
            self::DEBIT_CARD => 'info',
            self::BANK_TRANSFER => 'warning',
            self::CASH => 'success',
            self::WALLET => 'purple',
            self::PAYPAL => 'blue',
            self::APPLE_PAY => 'gray',
            self::GOOGLE_PAY => 'red',
            self::STC_PAY => 'purple',
        };
    }

    /**
     * Get the processing fee percentage for this method
     */
    public function feePercentage(): float
    {
        return match ($this) {
            self::CREDIT_CARD => 2.9,
            self::DEBIT_CARD => 2.5,
            self::BANK_TRANSFER => 0.5,
            self::CASH => 0.0,
            self::WALLET => 2.0,
            self::PAYPAL => 3.5,
            self::APPLE_PAY => 2.5,
            self::GOOGLE_PAY => 2.5,
            self::STC_PAY => 2.0,
        };
    }

    /**
     * Check if method requires online gateway
     */
    public function requiresGateway(): bool
    {
        return ! in_array($this, [self::CASH, self::BANK_TRANSFER]);
    }

    /**
     * Check if method is instant (no manual verification needed)
     */
    public function isInstant(): bool
    {
        return ! in_array($this, [self::BANK_TRANSFER, self::CASH]);
    }

    /**
     * Get all payment method values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get payment method options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($method) => $method->label(), self::cases())
        );
    }

    /**
     * Get online payment methods only
     */
    public static function onlineMethods(): array
    {
        return array_filter(
            self::cases(),
            fn ($method) => $method->requiresGateway()
        );
    }

    /**
     * Get manual payment methods only
     */
    public static function manualMethods(): array
    {
        return [self::CASH, self::BANK_TRANSFER];
    }
}
