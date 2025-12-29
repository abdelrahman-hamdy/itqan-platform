<?php

namespace App\Services;

use App\Http\Helpers\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ApiResponseService - Standardized API response formatting
 *
 * Provides consistent JSON response structure across all API endpoints.
 * Used by 420+ response()->json() calls throughout the application.
 *
 * Response format:
 * {
 *     "success": true|false,
 *     "message": "...",
 *     "data": {...} | [...] | null,
 *     "errors": [...],
 *     "meta": {
 *         "current_page": 1,
 *         "last_page": 10,
 *         "per_page": 15,
 *         "total": 150
 *     }
 * }
 *
 * @see app/Http/Controllers/Traits/ApiResponses.php for convenient controller access
 */
class ApiResponseService
{
    /**
     * Success response with data
     *
     * @param mixed $data Response data (array, object, Collection, or null)
     * @param string $message Success message
     * @param int $code HTTP status code (default: 200)
     * @return JsonResponse
     */
    public function success(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?: __('Operation completed successfully'),
            'data' => $this->normalizeData($data),
        ];

        return response()->json($response, $code);
    }

    /**
     * Error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param array $errors Additional error details
     * @return JsonResponse
     */
    public function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message ?: __('An error occurred'),
            'data' => null,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Created response (201)
     *
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return JsonResponse
     */
    public function created(mixed $data = null, string $message = ''): JsonResponse
    {
        return $this->success(
            data: $data,
            message: $message ?: __('Resource created successfully'),
            code: 201
        );
    }

    /**
     * No content response (204)
     *
     * @return JsonResponse
     */
    public function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Not found response (404)
     *
     * @param string $message Not found message
     * @return JsonResponse
     */
    public function notFound(string $message = ''): JsonResponse
    {
        return $this->error(
            message: $message ?: __('Resource not found'),
            code: 404
        );
    }

    /**
     * Unauthorized response (401)
     *
     * @param string $message Unauthorized message
     * @return JsonResponse
     */
    public function unauthorized(string $message = ''): JsonResponse
    {
        return $this->error(
            message: $message ?: __('Unauthorized access'),
            code: 401
        );
    }

    /**
     * Forbidden response (403)
     *
     * @param string $message Forbidden message
     * @return JsonResponse
     */
    public function forbidden(string $message = ''): JsonResponse
    {
        return $this->error(
            message: $message ?: __('Access forbidden'),
            code: 403
        );
    }

    /**
     * Validation error response (422)
     *
     * @param array $errors Validation errors
     * @param string $message Validation message
     * @return JsonResponse
     */
    public function validationError(array $errors, string $message = ''): JsonResponse
    {
        return $this->error(
            message: $message ?: __('Validation failed'),
            code: 422,
            errors: $errors
        );
    }

    /**
     * Server error response (500)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public function serverError(string $message = ''): JsonResponse
    {
        return $this->error(
            message: $message ?: __('Internal server error'),
            code: 500
        );
    }

    /**
     * Paginated response with standardized pagination structure
     *
     * Uses PaginationHelper for consistent pagination format across all endpoints.
     *
     * @param LengthAwarePaginator $paginator Paginator instance
     * @param string $message Success message
     * @return JsonResponse
     */
    public function paginated(LengthAwarePaginator $paginator, string $message = ''): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?: __('Data retrieved successfully'),
            'data' => $paginator->items(),
            'pagination' => PaginationHelper::fromPaginator($paginator),
        ];

        return response()->json($response, 200);
    }

    /**
     * Collection response
     *
     * @param Collection $collection Collection of items
     * @param string $message Success message
     * @return JsonResponse
     */
    public function collection(Collection $collection, string $message = ''): JsonResponse
    {
        return $this->success(
            data: $collection->values()->all(),
            message: $message ?: __('Data retrieved successfully')
        );
    }

    /**
     * Response with custom structure
     *
     * Allows full control over response structure while maintaining success flag
     *
     * @param array $data Custom response data
     * @param bool $success Success flag
     * @param int $code HTTP status code
     * @return JsonResponse
     */
    public function custom(array $data, bool $success = true, int $code = 200): JsonResponse
    {
        $response = array_merge(['success' => $success], $data);

        return response()->json($response, $code);
    }

    /**
     * Conditional response based on operation result
     *
     * @param bool $success Operation success status
     * @param string $successMessage Message if successful
     * @param string $errorMessage Message if failed
     * @param mixed $data Response data
     * @return JsonResponse
     */
    public function operationResult(
        bool $success,
        string $successMessage,
        string $errorMessage,
        mixed $data = null
    ): JsonResponse {
        if ($success) {
            return $this->success($data, $successMessage);
        }

        return $this->error($errorMessage);
    }

    /**
     * Normalize data for consistent response format
     *
     * @param mixed $data Input data
     * @return mixed Normalized data
     */
    protected function normalizeData(mixed $data): mixed
    {
        if ($data instanceof Collection) {
            return $data->values()->all();
        }

        return $data;
    }
}
