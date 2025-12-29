<?php

namespace App\Http\Traits\Api;

use App\Enums\Api\ErrorCode;
use App\Http\Helpers\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
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
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param string|ErrorCode|null $errorCode Error code (string or ErrorCode enum)
     * @param array $errors Additional errors
     * @param array $meta Additional metadata
     */
    protected function error(
        string $message,
        int $status = 400,
        string|ErrorCode|null $errorCode = null,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        // Convert ErrorCode enum to string value
        $codeValue = $errorCode instanceof ErrorCode
            ? $errorCode->value
            : ($errorCode ?? $this->getErrorCodeFromStatus($status));

        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $codeValue,
            'errors' => $errors,
            'meta' => array_merge($this->getBaseMeta(), $meta),
        ];

        return response()->json($response, $status);
    }

    /**
     * Return an error response using ErrorCode enum (preferred method)
     *
     * Uses the enum's httpStatus() for status code and label() for message if not provided.
     *
     * @param ErrorCode $errorCode The error code enum
     * @param string|null $message Custom message (uses enum label if null)
     * @param array $errors Additional errors
     * @param array $meta Additional metadata
     */
    protected function errorWithCode(
        ErrorCode $errorCode,
        ?string $message = null,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        return $this->error(
            message: $message ?? $errorCode->label(),
            status: $errorCode->httpStatus(),
            errorCode: $errorCode,
            errors: $errors,
            meta: $meta
        );
    }

    /**
     * Return a paginated response from ResourceCollection
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
            'pagination' => PaginationHelper::fromPaginator($paginator),
        ]);
    }

    /**
     * Return a paginated response from LengthAwarePaginator
     */
    protected function paginatedFromQuery(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        ?callable $transformer = null
    ): JsonResponse {
        $items = $transformer
            ? collect($paginator->items())->map($transformer)->values()->all()
            : $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => $this->getBaseMeta(),
            'pagination' => PaginationHelper::fromPaginator($paginator),
        ]);
    }

    /**
     * Return a paginated response from array/collection with manual pagination
     *
     * Use this when you have an array or collection that needs manual slicing
     */
    protected function paginatedFromArray(
        array $items,
        int $total,
        int $page,
        int $perPage,
        string $message = 'Success'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => $this->getBaseMeta(),
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ]);
    }

    /**
     * Paginate an array and return response
     *
     * Convenience method that handles slicing and pagination in one call
     */
    protected function paginateArray(
        $items,
        Request $request,
        string $message = 'Success',
        ?int $perPage = null
    ): JsonResponse {
        $result = PaginationHelper::paginateArray($items, $request, $perPage);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result['items'],
            'meta' => $this->getBaseMeta(),
            'pagination' => $result['pagination'],
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
     * Get meta with last_updated for cacheable resources
     *
     * Used for mobile apps to determine if cached data is still fresh.
     *
     * @param \DateTimeInterface|string|null $lastUpdated Last modification timestamp
     * @return array Meta array with last_updated field
     */
    protected function getMetaWithLastUpdated($lastUpdated = null): array
    {
        $meta = $this->getBaseMeta();

        if ($lastUpdated) {
            $meta['last_updated'] = $lastUpdated instanceof \DateTimeInterface
                ? $lastUpdated->format('c')
                : $lastUpdated;
        }

        return $meta;
    }

    /**
     * Return a cacheable success response with last_updated timestamp
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param \DateTimeInterface|string|null $lastUpdated Last modification timestamp
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    protected function successCacheable(
        mixed $data = null,
        string $message = 'Success',
        $lastUpdated = null,
        int $status = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $this->getMetaWithLastUpdated($lastUpdated),
        ];

        return response()->json($response, $status);
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
