<?php

namespace App\Services\Payment\DTOs;

use App\Enums\PaymentResultStatus;

/**
 * Data Transfer Object for payment operation results.
 *
 * Contains the outcome of payment creation, verification,
 * or refund operations from any gateway.
 */
readonly class PaymentResult
{
    public function __construct(
        public PaymentResultStatus $status,
        public ?string $transactionId = null,
        public ?string $gatewayOrderId = null,
        public ?string $redirectUrl = null,
        public ?string $iframeUrl = null,
        public ?string $clientSecret = null,
        public ?array $paymentKeys = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?string $errorMessageAr = null,
        public array $rawResponse = [],
        public array $metadata = [],
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $transactionId,
        ?string $gatewayOrderId = null,
        array $rawResponse = [],
        array $metadata = [],
    ): self {
        return new self(
            status: PaymentResultStatus::SUCCESS,
            transactionId: $transactionId,
            gatewayOrderId: $gatewayOrderId,
            rawResponse: $rawResponse,
            metadata: $metadata,
        );
    }

    /**
     * Create a pending result (payment initiated, awaiting completion).
     */
    public static function pending(
        ?string $transactionId = null,
        ?string $redirectUrl = null,
        ?string $iframeUrl = null,
        ?string $clientSecret = null,
        ?array $paymentKeys = null,
        array $rawResponse = [],
        array $metadata = [],
    ): self {
        return new self(
            status: PaymentResultStatus::PENDING,
            transactionId: $transactionId,
            redirectUrl: $redirectUrl,
            iframeUrl: $iframeUrl,
            clientSecret: $clientSecret,
            paymentKeys: $paymentKeys,
            rawResponse: $rawResponse,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failed(
        string $errorCode,
        string $errorMessage,
        ?string $errorMessageAr = null,
        ?string $transactionId = null,
        array $rawResponse = [],
    ): self {
        return new self(
            status: PaymentResultStatus::FAILED,
            transactionId: $transactionId,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            errorMessageAr: $errorMessageAr ?? self::translateError($errorCode),
            rawResponse: $rawResponse,
        );
    }

    /**
     * Create a cancelled result.
     */
    public static function cancelled(
        ?string $transactionId = null,
        ?string $reason = null,
        array $rawResponse = [],
    ): self {
        return new self(
            status: PaymentResultStatus::CANCELLED,
            transactionId: $transactionId,
            errorMessage: $reason ?? 'Payment was cancelled by user',
            errorMessageAr: $reason ?? __('payments.error_codes.cancelled_by_user'),
            rawResponse: $rawResponse,
        );
    }

    /**
     * Check if operation was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * Check if payment is pending/in progress.
     */
    public function isPending(): bool
    {
        return $this->status->isInProgress();
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentResultStatus::FAILED;
    }

    /**
     * Check if result has redirect URL.
     */
    public function hasRedirect(): bool
    {
        return ! empty($this->redirectUrl);
    }

    /**
     * Check if result has iframe URL.
     */
    public function hasIframe(): bool
    {
        return ! empty($this->iframeUrl);
    }

    /**
     * Get the display error message (Arabic preferred).
     */
    public function getDisplayError(): string
    {
        return $this->errorMessageAr ?? $this->errorMessage ?? __('payments.error_codes.unknown');
    }

    /**
     * Translate common error codes to Arabic.
     */
    private static function translateError(string $errorCode): string
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
            default => __('payments.error_codes.default_processing'),
        };
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'transaction_id' => $this->transactionId,
            'gateway_order_id' => $this->gatewayOrderId,
            'redirect_url' => $this->redirectUrl,
            'iframe_url' => $this->iframeUrl,
            'client_secret' => $this->clientSecret,
            'payment_keys' => $this->paymentKeys,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'error_message_ar' => $this->errorMessageAr,
            'metadata' => $this->metadata,
        ];
    }
}
