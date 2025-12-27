<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function __construct(
        string $message = 'Webhook validation failed',
        string $webhookType = 'unknown',
        array $validationErrors = []
    ) {
        parent::__construct($message, 400);
        $this->webhookType = $webhookType;
        $this->validationErrors = $validationErrors;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error' => 'webhook_validation_failed',
            'webhook_type' => $this->webhookType,
        ];

        if (! empty($this->validationErrors)) {
            $response['validation_errors'] = $this->validationErrors;
        }

        return response()->json($response, 400);
    }

    /**
     * Get the webhook type.
     */
    public function getWebhookType(): string
    {
        return $this->webhookType;
    }

    /**
     * Get the validation errors.
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
