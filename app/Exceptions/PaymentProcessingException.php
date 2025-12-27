<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exception for payment processing failures.
 *
 * Used when payment operations fail, including gateway errors,
 * validation failures, and processing issues.
 */
class PaymentProcessingException extends Exception
{
    protected array $errors;

    protected string $paymentId;

    public function __construct(
        string $message = 'فشل في معالجة الدفع',
        string $paymentId = '',
        array $errors = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->paymentId = $paymentId;
        $this->errors = $errors;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error' => 'payment_processing_failed',
        ];

        if ($this->paymentId) {
            $response['payment_id'] = $this->paymentId;
        }

        if (! empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, 400);
    }

    /**
     * Get the payment ID associated with this exception.
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * Get the errors array.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
