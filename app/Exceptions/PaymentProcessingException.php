<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Exception for payment processing failures.
 *
 * Used when payment operations fail, including gateway errors,
 * validation failures, and processing issues.
 */
class PaymentProcessingException extends Exception
{
    protected ?string $paymentId;

    protected ?array $gatewayResponse;

    protected ?string $errorCode;

    public function __construct(
        string $message,
        ?string $paymentId = null,
        ?array $gatewayResponse = null,
        ?string $errorCode = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->paymentId = $paymentId;
        $this->gatewayResponse = $gatewayResponse;
        $this->errorCode = $errorCode;
    }

    /**
     * Create exception from gateway error response
     */
    public static function fromGatewayError(
        string $gateway,
        array $gatewayResponse,
        ?string $paymentId = null
    ): self {
        $errorCode = $gatewayResponse['error_code'] ?? $gatewayResponse['code'] ?? 'UNKNOWN_ERROR';
        $gatewayMessage = $gatewayResponse['message'] ?? $gatewayResponse['error'] ?? 'حدث خطأ في معالجة الدفع';

        $message = sprintf(
            'فشلت عملية الدفع عبر %s: %s',
            $gateway,
            $gatewayMessage
        );

        return new self($message, $paymentId, $gatewayResponse, $errorCode);
    }

    /**
     * Create exception for declined payment
     */
    public static function paymentDeclined(
        string $reason,
        ?string $paymentId = null,
        ?array $gatewayResponse = null
    ): self {
        $message = sprintf('تم رفض عملية الدفع: %s', $reason);

        return new self($message, $paymentId, $gatewayResponse, 'PAYMENT_DECLINED');
    }

    /**
     * Create exception for timeout
     */
    public static function timeout(
        string $gateway,
        ?string $paymentId = null
    ): self {
        $message = sprintf('انتهت مهلة الاتصال ببوابة الدفع %s', $gateway);

        return new self($message, $paymentId, null, 'GATEWAY_TIMEOUT');
    }

    /**
     * Create exception for invalid amount
     */
    public static function invalidAmount(
        float $amount,
        ?string $paymentId = null
    ): self {
        $message = sprintf('المبلغ غير صالح: %s', $amount);

        return new self($message, $paymentId, null, 'INVALID_AMOUNT');
    }

    /**
     * Get payment ID
     */
    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    /**
     * Get gateway response
     */
    public function getGatewayResponse(): ?array
    {
        return $this->gatewayResponse;
    }

    /**
     * Get error code
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        Log::error('Payment processing failed', [
            'payment_id' => $this->paymentId,
            'error_code' => $this->errorCode,
            'gateway_response' => $this->gatewayResponse,
            'message' => $this->message,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        $statusCode = match ($this->errorCode) {
            'PAYMENT_DECLINED' => 402, // Payment Required
            'INVALID_AMOUNT' => 400, // Bad Request
            'GATEWAY_TIMEOUT' => 504, // Gateway Timeout
            default => 500, // Internal Server Error
        };

        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error' => [
                'type' => 'payment_processing_error',
                'code' => $this->errorCode,
                'payment_id' => $this->paymentId,
                'gateway_response' => $this->gatewayResponse,
            ],
        ], $statusCode);
    }
}
