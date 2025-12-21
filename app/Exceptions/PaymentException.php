<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for payment-related errors
 */
class PaymentException extends Exception
{
    public static function invalidAmount(): self
    {
        return new self('Invalid payment amount');
    }

    public static function processingFailed(string $reason = ''): self
    {
        $message = 'Payment processing failed';
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    public static function gatewayError(string $gateway, string $error): self
    {
        return new self("Payment gateway error ({$gateway}): {$error}");
    }

    public static function refundFailed(string $reason = ''): self
    {
        $message = 'Refund failed';
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }

    public static function transactionNotFound(string $transactionId): self
    {
        return new self("Transaction not found: {$transactionId}");
    }

    public static function alreadyPaid(): self
    {
        return new self('This item has already been paid for');
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency: {$currency}");
    }

    public static function expiredPaymentLink(): self
    {
        return new self('Payment link has expired');
    }

    public static function webhookVerificationFailed(): self
    {
        return new self('Payment webhook verification failed');
    }
}
