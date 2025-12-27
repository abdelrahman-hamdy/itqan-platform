<?php

namespace App\Services\Payment\Exceptions;

use Exception;
use App\Enums\SessionStatus;

/**
 * Exception for webhook validation failures.
 */
class WebhookValidationException extends Exception
{
    protected string $errorCode;
    protected ?string $gatewayName;
    protected array $context;

    public function __construct(
        string $message,
        string $errorCode = 'WEBHOOK_VALIDATION_FAILED',
        ?string $gatewayName = null,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->errorCode = $errorCode;
        $this->gatewayName = $gatewayName;
        $this->context = $context;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the gateway name.
     */
    public function getGatewayName(): ?string
    {
        return $this->gatewayName;
    }

    /**
     * Get additional context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create for invalid signature.
     */
    public static function invalidSignature(string $gatewayName): self
    {
        return new self(
            message: "Invalid webhook signature from {$gatewayName}",
            errorCode: 'INVALID_SIGNATURE',
            gatewayName: $gatewayName,
        );
    }

    /**
     * Create for duplicate event.
     */
    public static function duplicateEvent(string $eventId, string $gatewayName): self
    {
        return new self(
            message: "Duplicate webhook event: {$eventId}",
            errorCode: 'DUPLICATE_EVENT',
            gatewayName: $gatewayName,
            context: ['event_id' => $eventId],
        );
    }

    /**
     * Create for invalid payload.
     */
    public static function invalidPayload(string $gatewayName, string $reason): self
    {
        return new self(
            message: "Invalid webhook payload from {$gatewayName}: {$reason}",
            errorCode: 'INVALID_PAYLOAD',
            gatewayName: $gatewayName,
            context: ['reason' => $reason],
        );
    }

    /**
     * Create for tenant mismatch.
     */
    public static function tenantMismatch(int $expectedAcademyId, int $receivedAcademyId): self
    {
        return new self(
            message: "Webhook academy_id mismatch: expected {$expectedAcademyId}, got {$receivedAcademyId}",
            errorCode: 'TENANT_MISMATCH',
            context: [
                'expected_academy_id' => $expectedAcademyId,
                'received_academy_id' => $receivedAcademyId,
            ],
        );
    }

    /**
     * Create for amount mismatch.
     */
    public static function amountMismatch(int $expectedAmount, int $receivedAmount): self
    {
        return new self(
            message: "Webhook amount mismatch: expected {$expectedAmount}, got {$receivedAmount}",
            errorCode: 'AMOUNT_MISMATCH',
            context: [
                'expected_amount' => $expectedAmount,
                'received_amount' => $receivedAmount,
            ],
        );
    }
}
