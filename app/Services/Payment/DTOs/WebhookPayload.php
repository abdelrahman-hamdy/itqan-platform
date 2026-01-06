<?php

namespace App\Services\Payment\DTOs;

use App\Enums\PaymentResultStatus;

/**
 * Data Transfer Object for webhook payloads.
 *
 * Standardizes webhook data from different gateways into
 * a common format for processing.
 */
readonly class WebhookPayload
{
    public function __construct(
        public string $eventType,
        public string $transactionId,
        public PaymentResultStatus $status,
        public int $amountInCents,
        public string $currency,
        public string $gateway,
        public ?string $orderId = null,
        public ?string $paymentMethod = null,
        public ?int $paymentId = null,
        public ?int $academyId = null,
        public bool $isSuccess = false,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?string $cardBrand = null,
        public ?string $cardLastFour = null,
        public ?\DateTimeInterface $processedAt = null,
        public array $rawPayload = [],
        public array $metadata = [],
    ) {}

    /**
     * Create from Paymob webhook data.
     */
    public static function fromPaymob(array $data): self
    {
        $obj = $data['obj'] ?? $data;
        $isSuccess = ($obj['success'] ?? false) === true;

        // Determine status from Paymob response
        $status = match (true) {
            $isSuccess => PaymentResultStatus::SUCCESS,
            ($obj['pending'] ?? false) => PaymentResultStatus::PENDING,
            ($obj['is_voided'] ?? false) => PaymentResultStatus::CANCELLED,
            ($obj['is_refunded'] ?? false) => PaymentResultStatus::REFUNDED,
            default => PaymentResultStatus::FAILED,
        };

        // Extract payment ID from merchant_order_id or metadata
        $merchantOrderId = $obj['merchant_order_id'] ?? $obj['order']['merchant_order_id'] ?? null;
        $paymentId = null;
        $academyId = null;

        if ($merchantOrderId && str_contains($merchantOrderId, '-')) {
            // Format: ACADEMY_ID-PAYMENT_ID-TIMESTAMP
            $parts = explode('-', $merchantOrderId);
            if (count($parts) >= 2) {
                $academyId = (int) $parts[0];
                $paymentId = (int) $parts[1];
            }
        }

        // Extract card info if available
        $sourceData = $obj['source_data'] ?? [];
        $cardBrand = $sourceData['sub_type'] ?? null;
        $cardLastFour = $sourceData['pan'] ?? null;

        return new self(
            eventType: $data['type'] ?? 'TRANSACTION',
            transactionId: (string) ($obj['id'] ?? ''),
            status: $status,
            amountInCents: (int) (($obj['amount_cents'] ?? 0)),
            currency: $obj['currency'] ?? 'EGP',
            gateway: 'paymob',
            orderId: (string) ($obj['order']['id'] ?? $obj['order_id'] ?? ''),
            paymentMethod: $sourceData['type'] ?? 'card',
            paymentId: $paymentId,
            academyId: $academyId,
            isSuccess: $isSuccess,
            errorCode: $isSuccess ? null : ($obj['data']['txn_response_code'] ?? 'UNKNOWN'),
            errorMessage: $isSuccess ? null : ($obj['data']['message'] ?? 'Payment failed'),
            cardBrand: $cardBrand,
            cardLastFour: $cardLastFour,
            processedAt: isset($obj['created_at']) ? new \DateTime($obj['created_at']) : now(),
            rawPayload: $data,
            metadata: $obj['payment_key_claims']['extra'] ?? [],
        );
    }

    /**
     * Get amount in major currency units.
     */
    public function getAmountInMajorUnits(): float
    {
        return $this->amountInCents / 100;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->getAmountInMajorUnits(), 2).' '.$this->currency;
    }

    /**
     * Check if this is a successful payment.
     */
    public function isSuccessful(): bool
    {
        return $this->isSuccess && $this->status === PaymentResultStatus::SUCCESS;
    }

    /**
     * Check if this is a refund event.
     */
    public function isRefund(): bool
    {
        return in_array($this->eventType, ['REFUND', 'REFUNDED']);
    }

    /**
     * Check if this is a void/cancel event.
     */
    public function isVoid(): bool
    {
        return in_array($this->eventType, ['VOID', 'VOIDED']);
    }

    /**
     * Get unique identifier for idempotency.
     */
    public function getIdempotencyKey(): string
    {
        return sprintf('%s-%s-%s', $this->gateway, $this->transactionId, $this->eventType);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'transaction_id' => $this->transactionId,
            'status' => $this->status->value,
            'amount_cents' => $this->amountInCents,
            'currency' => $this->currency,
            'gateway' => $this->gateway,
            'order_id' => $this->orderId,
            'payment_method' => $this->paymentMethod,
            'payment_id' => $this->paymentId,
            'academy_id' => $this->academyId,
            'is_success' => $this->isSuccess,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'card_brand' => $this->cardBrand,
            'card_last_four' => $this->cardLastFour,
            'processed_at' => $this->processedAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
