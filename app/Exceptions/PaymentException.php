<?php

namespace App\Exceptions;

use Throwable;
use Exception;

/**
 * Exception for payment-related errors.
 *
 * Used by PaymentService and related payment operations.
 */
class PaymentException extends Exception
{
    public const INVALID_STATUS = 'INVALID_STATUS';

    public const GATEWAY_ERROR = 'GATEWAY_ERROR';

    public const REFUND_ERROR = 'REFUND_ERROR';

    public const AMOUNT_MISMATCH = 'AMOUNT_MISMATCH';

    public const ALREADY_PROCESSED = 'ALREADY_PROCESSED';

    public const WEBHOOK_ERROR = 'WEBHOOK_ERROR';

    public const NOT_FOUND = 'NOT_FOUND';

    protected string $errorCode;

    protected array $context;

    public function __construct(
        string $message,
        string $errorCode = 'PAYMENT_ERROR',
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
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

    public static function gatewayError(string $gateway, string $message, ?string $gatewayCode = null): self
    {
        return new self(
            "Payment gateway error ({$gateway}): {$message}",
            self::GATEWAY_ERROR,
            ['gateway' => $gateway, 'message' => $message, 'gateway_code' => $gatewayCode]
        );
    }

    public static function refundError(int $paymentId, string $reason): self
    {
        return new self(
            "Refund failed for payment {$paymentId}: {$reason}",
            self::REFUND_ERROR,
            ['payment_id' => $paymentId, 'reason' => $reason]
        );
    }

    public static function amountMismatch(float $expected, float $received): self
    {
        return new self(
            "Amount mismatch. Expected: {$expected}, Received: {$received}",
            self::AMOUNT_MISMATCH,
            ['expected' => $expected, 'received' => $received]
        );
    }

    public static function alreadyProcessed(int $paymentId): self
    {
        return new self(
            'This payment has already been processed',
            self::ALREADY_PROCESSED,
            ['payment_id' => $paymentId]
        );
    }

    public static function webhookError(string $gateway, string $reason): self
    {
        return new self(
            "Webhook error from {$gateway}: {$reason}",
            self::WEBHOOK_ERROR,
            ['gateway' => $gateway, 'reason' => $reason]
        );
    }

    public static function notFound(int $id): self
    {
        return new self(
            "Payment not found with ID: {$id}",
            self::NOT_FOUND,
            ['payment_id' => $id]
        );
    }
}
