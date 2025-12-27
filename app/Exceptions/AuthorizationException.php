<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exception for authorization failures.
 *
 * Used when a user doesn't have permission to perform an action
 * or access a resource.
 */
class AuthorizationException extends Exception
{
    protected int $statusCode;

    public function __construct(
        string $message = 'غير مصرح بالوصول',
        int $statusCode = 403
    ) {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error' => 'authorization_failed',
        ], $this->statusCode);
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
