<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for subscription-related errors
 */
class SubscriptionException extends Exception
{
    public static function invalidState(string $action, string $currentState): self
    {
        return new self("Cannot {$action} subscription in state: {$currentState}");
    }

    public static function cannotCancel(): self
    {
        return new self('Cannot cancel subscription in current state');
    }

    public static function cannotRenew(): self
    {
        return new self('Cannot renew subscription in current state');
    }

    public static function autoRenewalNotSupported(): self
    {
        return new self('This billing cycle does not support auto-renewal');
    }

    public static function notFound(string|int $id): self
    {
        return new self("Subscription {$id} not found");
    }

    public static function expired(): self
    {
        return new self('Subscription has expired');
    }

    public static function sessionLimitReached(): self
    {
        return new self('Session limit for this subscription has been reached');
    }

    public static function paymentRequired(): self
    {
        return new self('Payment required to activate subscription');
    }

    public static function certificateAlreadyIssued(): self
    {
        return new self('Certificate already issued for this subscription');
    }

    public static function notEligibleForCertificate(): self
    {
        return new self('Subscription not eligible for certificate');
    }
}
