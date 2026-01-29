<?php

namespace App\Services\Payment\DTOs;

/**
 * Data Transfer Object for card tokenization results.
 *
 * This DTO encapsulates the result of a card tokenization operation,
 * including the token and card details on success, or error information
 * on failure.
 */
readonly class TokenizationResult
{
    public function __construct(
        public bool $success,
        public ?string $token = null,
        public ?string $cardBrand = null,
        public ?string $lastFour = null,
        public ?string $expiryMonth = null,
        public ?string $expiryYear = null,
        public ?string $holderName = null,
        public ?string $gatewayCustomerId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?string $errorMessageAr = null,
        public array $rawResponse = [],
        public array $metadata = [],
    ) {}

    /**
     * Create a successful tokenization result
     */
    public static function success(
        string $token,
        ?string $cardBrand = null,
        ?string $lastFour = null,
        ?string $expiryMonth = null,
        ?string $expiryYear = null,
        ?string $holderName = null,
        ?string $gatewayCustomerId = null,
        array $rawResponse = [],
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            token: $token,
            cardBrand: $cardBrand,
            lastFour: $lastFour,
            expiryMonth: $expiryMonth,
            expiryYear: $expiryYear,
            holderName: $holderName,
            gatewayCustomerId: $gatewayCustomerId,
            rawResponse: $rawResponse,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed tokenization result
     */
    public static function failed(
        string $errorCode,
        string $errorMessage,
        ?string $errorMessageAr = null,
        array $rawResponse = [],
    ): self {
        return new self(
            success: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            errorMessageAr: $errorMessageAr ?? 'فشل في حفظ البطاقة',
            rawResponse: $rawResponse,
        );
    }

    /**
     * Check if tokenization was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success && $this->token !== null;
    }

    /**
     * Check if tokenization failed
     */
    public function isFailed(): bool
    {
        return ! $this->success;
    }

    /**
     * Get the appropriate error message for display (Arabic preferred)
     */
    public function getDisplayError(): string
    {
        return $this->errorMessageAr ?? $this->errorMessage ?? 'حدث خطأ غير متوقع';
    }

    /**
     * Get a masked display of the card for user confirmation
     */
    public function getMaskedCard(): string
    {
        if ($this->lastFour) {
            return '**** **** **** '.$this->lastFour;
        }

        return '****';
    }

    /**
     * Get card expiry in display format
     */
    public function getExpiryDisplay(): ?string
    {
        if ($this->expiryMonth && $this->expiryYear) {
            $year = strlen($this->expiryYear) === 4
                ? substr($this->expiryYear, -2)
                : $this->expiryYear;

            return sprintf('%s/%s', $this->expiryMonth, $year);
        }

        return null;
    }

    /**
     * Get brand display name in Arabic
     */
    public function getBrandDisplayName(): string
    {
        return match (strtolower($this->cardBrand ?? '')) {
            'visa' => 'فيزا',
            'mastercard' => 'ماستركارد',
            'meeza' => 'ميزة',
            'amex', 'american express' => 'أمريكان إكسبريس',
            default => $this->cardBrand ?? '',
        };
    }

    /**
     * Convert to array for storage or logging
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'token' => $this->success ? '***REDACTED***' : null, // Don't expose token
            'card_brand' => $this->cardBrand,
            'last_four' => $this->lastFour,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'holder_name' => $this->holderName,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }

    /**
     * Create array suitable for creating SavedPaymentMethod
     */
    public function toSavedPaymentMethodData(string $gateway, string $type = 'card'): array
    {
        return [
            'gateway' => $gateway,
            'token' => $this->token,
            'gateway_customer_id' => $this->gatewayCustomerId,
            'type' => $type,
            'brand' => $this->cardBrand,
            'last_four' => $this->lastFour,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'holder_name' => $this->holderName,
            'metadata' => $this->metadata,
        ];
    }
}
