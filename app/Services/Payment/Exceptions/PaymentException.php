<?php

namespace App\Services\Payment\Exceptions;

use Exception;

/**
 * Base exception for payment-related errors.
 */
class PaymentException extends Exception
{
    protected string $errorCode;

    protected ?string $errorMessageAr;

    protected ?string $gatewayName;

    protected array $context;

    public function __construct(
        string $message,
        string $errorCode = 'PAYMENT_ERROR',
        ?string $errorMessageAr = null,
        ?string $gatewayName = null,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->errorCode = $errorCode;
        $this->errorMessageAr = $errorMessageAr ?? $this->translateError($errorCode);
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
     * Get the Arabic error message.
     */
    public function getErrorMessageAr(): string
    {
        return $this->errorMessageAr ?? __('payments.error_codes.default');
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
     * Create from a gateway error.
     */
    public static function fromGatewayError(
        string $gatewayName,
        string $errorCode,
        string $errorMessage,
        array $context = []
    ): self {
        return new self(
            message: $errorMessage,
            errorCode: $errorCode,
            gatewayName: $gatewayName,
            context: $context,
        );
    }

    /**
     * Create for insufficient configuration.
     */
    public static function notConfigured(string $gatewayName): self
    {
        return new self(
            message: "Payment gateway '{$gatewayName}' is not properly configured",
            errorCode: 'GATEWAY_NOT_CONFIGURED',
            errorMessageAr: __('payments.exception.gateway_not_configured', ['gateway' => $gatewayName]),
            gatewayName: $gatewayName,
        );
    }

    /**
     * Create for invalid amount.
     */
    public static function invalidAmount(float $amount): self
    {
        return new self(
            message: "Invalid payment amount: {$amount}",
            errorCode: 'INVALID_AMOUNT',
            errorMessageAr: __('payments.error_codes.invalid_amount'),
            context: ['amount' => $amount],
        );
    }

    /**
     * Create for duplicate payment.
     */
    public static function duplicatePayment(string $paymentId): self
    {
        return new self(
            message: "Duplicate payment attempt for payment ID: {$paymentId}",
            errorCode: 'DUPLICATE_PAYMENT',
            errorMessageAr: __('payments.error_codes.duplicate_payment'),
            context: ['payment_id' => $paymentId],
        );
    }

    /**
     * Translate common error codes to Arabic.
     */
    private function translateError(string $errorCode): string
    {
        return match ($errorCode) {
            'INSUFFICIENT_FUNDS' => __('payments.error_codes.insufficient_funds'),
            'CARD_DECLINED' => __('payments.error_codes.card_declined'),
            'EXPIRED_CARD' => __('payments.error_codes.expired_card'),
            'INVALID_CARD' => __('payments.error_codes.invalid_card'),
            'PROCESSING_ERROR' => __('payments.error_codes.processing_error'),
            'AUTHENTICATION_FAILED' => __('payments.error_codes.authentication_failed'),
            'TIMEOUT' => __('payments.error_codes.timeout'),
            'DUPLICATE_TRANSACTION' => __('payments.error_codes.duplicate_transaction'),
            'AMOUNT_LIMIT_EXCEEDED' => __('payments.error_codes.amount_limit_exceeded'),
            'GATEWAY_NOT_CONFIGURED' => __('payments.error_codes.gateway_not_configured'),
            'INVALID_AMOUNT' => __('payments.error_codes.invalid_amount'),
            'DUPLICATE_PAYMENT' => __('payments.error_codes.duplicate_payment'),
            default => __('payments.error_codes.default'),
        };
    }
}
