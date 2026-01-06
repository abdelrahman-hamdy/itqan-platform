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
        return $this->errorMessageAr ?? 'حدث خطأ في عملية الدفع';
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
            errorMessageAr: "بوابة الدفع '{$gatewayName}' غير مهيأة",
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
            errorMessageAr: 'مبلغ الدفع غير صحيح',
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
            errorMessageAr: 'تم محاولة دفع مكررة',
            context: ['payment_id' => $paymentId],
        );
    }

    /**
     * Translate common error codes to Arabic.
     */
    private function translateError(string $errorCode): string
    {
        return match ($errorCode) {
            'INSUFFICIENT_FUNDS' => 'رصيد غير كافٍ',
            'CARD_DECLINED' => 'تم رفض البطاقة',
            'EXPIRED_CARD' => 'البطاقة منتهية الصلاحية',
            'INVALID_CARD' => 'بيانات البطاقة غير صحيحة',
            'PROCESSING_ERROR' => 'خطأ في معالجة الدفع',
            'AUTHENTICATION_FAILED' => 'فشل التحقق من البطاقة',
            'TIMEOUT' => 'انتهت مهلة العملية',
            'DUPLICATE_TRANSACTION' => 'معاملة مكررة',
            'AMOUNT_LIMIT_EXCEEDED' => 'تجاوز الحد المسموح',
            'GATEWAY_NOT_CONFIGURED' => 'بوابة الدفع غير مهيأة',
            'INVALID_AMOUNT' => 'مبلغ الدفع غير صحيح',
            'DUPLICATE_PAYMENT' => 'محاولة دفع مكررة',
            default => 'حدث خطأ في عملية الدفع',
        };
    }
}
