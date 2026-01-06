<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Exception for webhook validation failures.
 *
 * Used when webhook payloads fail signature verification,
 * schema validation, or other validation checks.
 */
class WebhookValidationException extends Exception
{
    protected string $webhookType;

    protected array $validationErrors;

    protected ?array $payload;

    public function __construct(
        string $message,
        string $webhookType,
        array $validationErrors = [],
        ?array $payload = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->webhookType = $webhookType;
        $this->validationErrors = $validationErrors;
        $this->payload = $payload;
    }

    /**
     * Create exception for invalid signature
     */
    public static function invalidSignature(
        string $webhookType,
        string $receivedSignature,
        ?array $payload = null
    ): self {
        $message = sprintf(
            'التوقيع الإلكتروني غير صالح لـ webhook من نوع: %s',
            $webhookType
        );

        $validationErrors = [
            'signature' => 'التوقيع الإلكتروني المستلم لا يطابق التوقيع المتوقع',
            'received_signature' => $receivedSignature,
        ];

        return new self($message, $webhookType, $validationErrors, $payload);
    }

    /**
     * Create exception for missing required fields
     */
    public static function missingFields(
        string $webhookType,
        array $missingFields,
        ?array $payload = null
    ): self {
        $fieldsString = implode('، ', $missingFields);

        $message = sprintf(
            'حقول مطلوبة مفقودة في webhook من نوع %s: %s',
            $webhookType,
            $fieldsString
        );

        $validationErrors = [
            'missing_fields' => $missingFields,
            'message' => 'البيانات المستلمة غير كاملة',
        ];

        return new self($message, $webhookType, $validationErrors, $payload);
    }

    /**
     * Create exception for invalid payload format
     */
    public static function invalidFormat(
        string $webhookType,
        string $reason,
        ?array $payload = null
    ): self {
        $message = sprintf(
            'تنسيق البيانات غير صالح في webhook من نوع %s: %s',
            $webhookType,
            $reason
        );

        $validationErrors = [
            'format' => $reason,
        ];

        return new self($message, $webhookType, $validationErrors, $payload);
    }

    /**
     * Create exception for unsupported webhook type
     */
    public static function unsupportedType(
        string $webhookType,
        ?array $payload = null
    ): self {
        $message = sprintf(
            'نوع webhook غير مدعوم: %s',
            $webhookType
        );

        $validationErrors = [
            'webhook_type' => $webhookType,
            'message' => 'نوع الإشعار الوارد غير مدعوم في النظام',
        ];

        return new self($message, $webhookType, $validationErrors, $payload);
    }

    /**
     * Create exception for expired webhook
     */
    public static function expired(
        string $webhookType,
        string $timestamp,
        ?array $payload = null
    ): self {
        $message = sprintf(
            'انتهت صلاحية webhook من نوع %s - الطابع الزمني: %s',
            $webhookType,
            $timestamp
        );

        $validationErrors = [
            'timestamp' => $timestamp,
            'message' => 'الإشعار الوارد قديم جداً ولا يمكن معالجته',
        ];

        return new self($message, $webhookType, $validationErrors, $payload);
    }

    /**
     * Create exception for duplicate webhook
     */
    public static function duplicate(
        string $webhookType,
        string $webhookId,
        ?array $payload = null
    ): self {
        $message = sprintf(
            'webhook مكرر من نوع %s - المعرف: %s',
            $webhookType,
            $webhookId
        );

        $validationErrors = [
            'webhook_id' => $webhookId,
            'message' => 'تم معالجة هذا الإشعار مسبقاً',
        ];

        return new self($message, $webhookType, $validationErrors, $payload);
    }

    /**
     * Get webhook type
     */
    public function getWebhookType(): string
    {
        return $this->webhookType;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get payload
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        // Sanitize payload for logging (remove sensitive data)
        $sanitizedPayload = $this->payload;
        if ($sanitizedPayload) {
            unset(
                $sanitizedPayload['card_number'],
                $sanitizedPayload['cvv'],
                $sanitizedPayload['password'],
                $sanitizedPayload['secret']
            );
        }

        Log::warning('Webhook validation failed', [
            'webhook_type' => $this->webhookType,
            'validation_errors' => $this->validationErrors,
            'payload' => $sanitizedPayload,
            'message' => $this->message,
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        // Determine status code based on error type
        $statusCode = 400; // Bad Request by default

        if (isset($this->validationErrors['signature'])) {
            $statusCode = 401; // Unauthorized for signature failures
        } elseif (isset($this->validationErrors['webhook_id'])) {
            $statusCode = 409; // Conflict for duplicates
        } elseif (isset($this->validationErrors['webhook_type'])) {
            $statusCode = 422; // Unprocessable Entity for unsupported types
        }

        return response()->json([
            'success' => false,
            'message' => $this->message,
            'error' => [
                'type' => 'webhook_validation_error',
                'webhook_type' => $this->webhookType,
                'validation_errors' => $this->validationErrors,
            ],
        ], $statusCode);
    }
}
