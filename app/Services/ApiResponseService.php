<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Standardized API response service.
 *
 * Provides consistent JSON response formatting across all API controllers.
 * Eliminates duplication of response structure across 420+ response()->json() calls.
 */
class ApiResponseService
{
    /**
     * Return a successful response with optional data and message.
     */
    public function success(
        array|Collection $data = [],
        string $message = '',
        int $code = 200
    ): JsonResponse {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        if (! empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response.
     */
    public function error(
        string $message,
        int $code = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated response.
     */
    public function paginated(
        LengthAwarePaginator $paginator,
        string $message = '',
        ?string $dataKey = 'items'
    ): JsonResponse {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        $response['data'] = [
            $dataKey => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];

        return response()->json($response);
    }

    /**
     * Return a 404 not found response.
     */
    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return a 401 unauthorized response.
     */
    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a 403 forbidden response.
     */
    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a 422 validation error response.
     */
    public function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Return a 500 server error response.
     */
    public function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, 500);
    }

    /**
     * Return a 201 created response.
     */
    public function created(array|Collection $data = [], string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a 204 no content response (for deletions).
     */
    public function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a response for an operation result (success or error based on result).
     */
    public function operationResult(
        bool $success,
        string $successMessage,
        string $errorMessage,
        array $data = []
    ): JsonResponse {
        if ($success) {
            return $this->success($data, $successMessage);
        }

        return $this->error($errorMessage);
    }

    /**
     * Transform a collection with a callback and return success response.
     */
    public function collection(
        Collection $items,
        callable $transformer,
        string $message = ''
    ): JsonResponse {
        $transformed = $items->map($transformer)->values();

        return $this->success(['items' => $transformed], $message);
    }
}
