<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Payment;

/**
 * Data Transfer Object for Payment Processing Results
 *
 * Represents the result of a payment processing operation,
 * including success/failure status, payment record, and any errors.
 *
 * @property-read bool $success Whether the payment was successful
 * @property-read Payment|null $payment Payment model instance
 * @property-read float $amount Payment amount
 * @property-read string $currency Currency code (e.g., 'SAR', 'USD')
 * @property-read string|null $transactionId Internal transaction identifier
 * @property-read string|null $gatewayReference Payment gateway reference/receipt
 * @property-read string|null $paymentUrl Payment page URL (for redirects)
 * @property-read string|null $errorMessage Error message if payment failed
 * @property-read array $errors Validation errors array
 * @property-read array $metadata Additional payment metadata
 */
readonly class PaymentProcessingResult
{
    public function __construct(
        public bool $success,
        public ?Payment $payment = null,
        public float $amount = 0.0,
        public string $currency = 'SAR',
        public ?string $transactionId = null,
        public ?string $gatewayReference = null,
        public ?string $paymentUrl = null,
        public ?string $errorMessage = null,
        public array $errors = [],
        public array $metadata = [],
    ) {}

    /**
     * Create a successful payment result
     */
    public static function success(
        Payment $payment,
        float $amount,
        string $currency = 'SAR',
        ?string $transactionId = null,
        ?string $gatewayReference = null,
        ?string $paymentUrl = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            payment: $payment,
            amount: $amount,
            currency: $currency,
            transactionId: $transactionId ?? $payment->transaction_id,
            gatewayReference: $gatewayReference,
            paymentUrl: $paymentUrl,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed payment result
     */
    public static function failure(
        string $errorMessage,
        float $amount,
        string $currency = 'SAR',
        array $errors = [],
        ?Payment $payment = null,
        ?string $transactionId = null,
        array $metadata = []
    ): self {
        return new self(
            success: false,
            payment: $payment,
            amount: $amount,
            currency: $currency,
            transactionId: $transactionId,
            errorMessage: $errorMessage,
            errors: $errors,
            metadata: $metadata,
        );
    }

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            payment: $data['payment'] ?? null,
            amount: (float) ($data['amount'] ?? 0),
            currency: $data['currency'] ?? 'SAR',
            transactionId: $data['transactionId'] ?? $data['transaction_id'] ?? null,
            gatewayReference: $data['gatewayReference'] ?? $data['gateway_reference'] ?? null,
            paymentUrl: $data['paymentUrl'] ?? $data['payment_url'] ?? null,
            errorMessage: $data['errorMessage'] ?? $data['error_message'] ?? null,
            errors: $data['errors'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array for JSON responses
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'payment_id' => $this->payment?->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_id' => $this->transactionId,
            'gateway_reference' => $this->gatewayReference,
            'payment_url' => $this->paymentUrl,
            'error_message' => $this->errorMessage,
            'errors' => $this->errors,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if payment was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Check if payment requires redirect
     */
    public function requiresRedirect(): bool
    {
        return $this->success && ! empty($this->paymentUrl);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
