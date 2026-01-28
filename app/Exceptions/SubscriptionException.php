<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception for subscription-related errors.
 *
 * Used by SubscriptionService and related subscription operations.
 */
class SubscriptionException extends Exception
{
    public const INVALID_STATUS = 'INVALID_STATUS';

    public const ALREADY_CANCELLED = 'ALREADY_CANCELLED';

    public const INSUFFICIENT_SESSIONS = 'INSUFFICIENT_SESSIONS';

    public const BILLING_CYCLE_ERROR = 'BILLING_CYCLE_ERROR';

    public const RENEWAL_ERROR = 'RENEWAL_ERROR';

    public const NOT_FOUND = 'NOT_FOUND';

    protected string $errorCode;

    protected array $context;

    public function __construct(
        string $message,
        string $errorCode = 'SUBSCRIPTION_ERROR',
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function invalidStatus(string $currentStatus, string $requiredStatus): self
    {
        return new self(
            "Cannot perform this action. Current status is '{$currentStatus}', required: '{$requiredStatus}'",
            self::INVALID_STATUS,
            ['current_status' => $currentStatus, 'required_status' => $requiredStatus]
        );
    }

    public static function alreadyCancelled(int $subscriptionId): self
    {
        return new self(
            'This subscription has already been cancelled',
            self::ALREADY_CANCELLED,
            ['subscription_id' => $subscriptionId]
        );
    }

    public static function insufficientSessions(int $remaining, int $required): self
    {
        return new self(
            "Insufficient sessions. Remaining: {$remaining}, Required: {$required}",
            self::INSUFFICIENT_SESSIONS,
            ['remaining' => $remaining, 'required' => $required]
        );
    }

    public static function billingCycleError(string $reason): self
    {
        return new self(
            "Billing cycle error: {$reason}",
            self::BILLING_CYCLE_ERROR,
            ['reason' => $reason]
        );
    }

    public static function notFound(int $id): self
    {
        return new self(
            "Subscription not found with ID: {$id}",
            self::NOT_FOUND,
            ['subscription_id' => $id]
        );
    }
}
