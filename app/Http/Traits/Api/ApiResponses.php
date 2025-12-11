<?php

namespace App\Http\Traits\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

trait ApiResponses
{
    /**
     * Return a success response
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge($this->getBaseMeta(), $meta),
        ];

        return response()->json($response, $status);
    }

    /**
     * Return an error response
     */
    protected function error(
        string $message,
        int $status = 400,
        ?string $errorCode = null,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode ?? $this->getErrorCodeFromStatus($status),
            'errors' => $errors,
            'meta' => array_merge($this->getBaseMeta(), $meta),
        ];

        return response()->json($response, $status);
    }

    /**
     * Return a paginated response
     */
    protected function paginated(
        ResourceCollection $collection,
        string $message = 'Success'
    ): JsonResponse {
        $paginator = $collection->resource;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection->resolve(),
            'meta' => $this->getBaseMeta(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Return a resource response
     */
    protected function resource(
        JsonResource $resource,
        string $message = 'Success',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource->resolve(),
            'meta' => $this->getBaseMeta(),
        ], $status);
    }

    /**
     * Return a created response (201)
     */
    protected function created(
        mixed $data = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a no content response (204)
     */
    protected function noContent(string $message = 'Deleted successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'meta' => $this->getBaseMeta(),
        ], 200); // Using 200 instead of 204 to include message
    }

    /**
     * Return a validation error response
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401, 'UNAUTHENTICATED');
    }

    /**
     * Return a forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403, 'FORBIDDEN');
    }

    /**
     * Return a not found response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404, 'RESOURCE_NOT_FOUND');
    }

    /**
     * Return a server error response
     */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, 500, 'INTERNAL_ERROR');
    }

    /**
     * Get base meta information
     */
    protected function getBaseMeta(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'request_id' => request()->header('X-Request-ID', (string) Str::uuid()),
            'api_version' => 'v1',
        ];
    }

    /**
     * Get error code from HTTP status
     */
    protected function getErrorCodeFromStatus(int $status): string
    {
        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_ERROR',
            502 => 'BAD_GATEWAY',
            503 => 'SERVICE_UNAVAILABLE',
            504 => 'GATEWAY_TIMEOUT',
            default => 'UNKNOWN_ERROR',
        };
    }
}
