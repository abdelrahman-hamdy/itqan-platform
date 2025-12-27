<?php

namespace App\DTOs;

use App\Models\Payment;

/**
 * Data Transfer Object for Payment Processing Results
 *
 * Represents the result of a payment processing operation,
 * including success/failure status, payment record, and any errors.
 */
class PaymentProcessingResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?Payment $payment = null,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $errors = [],
        public readonly ?string $gatewayResponse = null,
    ) {}

    /**
     * Create a successful payment result
     */
    public static function success(
        Payment $payment,
        ?string $paymentUrl = null,
        ?string $transactionId = null,
        ?string $gatewayResponse = null
    ): self {
        return new self(
            success: true,
            payment: $payment,
            paymentUrl: $paymentUrl,
            transactionId: $transactionId,
            gatewayResponse: $gatewayResponse,
        );
    }

    /**
     * Create a failed payment result
     */
    public static function failure(
        string $errorMessage,
        array $errors = [],
        ?Payment $payment = null,
        ?string $gatewayResponse = null
    ): self {
        return new self(
            success: false,
            payment: $payment,
            errorMessage: $errorMessage,
            errors: $errors,
            gatewayResponse: $gatewayResponse,
        );
    }

    /**
     * Convert to array for JSON responses
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
        ];

        if ($this->success) {
            $result['payment_id'] = $this->payment?->id;
            $result['payment_url'] = $this->paymentUrl;
            $result['transaction_id'] = $this->transactionId;
        } else {
            $result['error'] = $this->errorMessage;
            if (! empty($this->errors)) {
                $result['errors'] = $this->errors;
            }
        }

        return $result;
    }

    /**
     * Check if payment requires redirect
     */
    public function requiresRedirect(): bool
    {
        return $this->success && ! empty($this->paymentUrl);
    }
}
