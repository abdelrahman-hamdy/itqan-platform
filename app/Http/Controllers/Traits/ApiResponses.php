<?php

namespace App\Http\Controllers\Traits;

use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ApiResponses Trait
 *
 * Provides convenient access to ApiResponseService methods in controllers.
 * Eliminates the need to inject the service in every controller constructor.
 *
 * Usage in controller:
 * ```php
 * use App\Http\Controllers\Traits\ApiResponses;
 *
 * class MyController extends Controller
 * {
 *     use ApiResponses;
 *
 *     public function index()
 *     {
 *         return $this->successResponse($data, 'Data retrieved successfully');
 *     }
 * }
 * ```
 */
trait ApiResponses
{
    /**
     * Get the API response service instance
     *
     * @return ApiResponseService
     */
    protected function apiResponse(): ApiResponseService
    {
        return app(ApiResponseService::class);
    }

    /**
     * Success response with data
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $code HTTP status code
     * @return JsonResponse
     */
    protected function successResponse(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        return $this->apiResponse()->success($data, $message, $code);
    }

    /**
     * Error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array $errors Additional error details
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return $this->apiResponse()->error($message, $code, $errors);
    }

    /**
     * Created response (201)
     *
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function createdResponse(mixed $data = null, string $message = ''): JsonResponse
    {
        return $this->apiResponse()->created($data, $message);
    }

    /**
     * No content response (204)
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return $this->apiResponse()->noContent();
    }

    /**
     * Not found response (404)
     *
     * @param string $message Not found message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = ''): JsonResponse
    {
        return $this->apiResponse()->notFound($message);
    }

    /**
     * Unauthorized response (401)
     *
     * @param string $message Unauthorized message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = ''): JsonResponse
    {
        return $this->apiResponse()->unauthorized($message);
    }

    /**
     * Forbidden response (403)
     *
     * @param string $message Forbidden message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = ''): JsonResponse
    {
        return $this->apiResponse()->forbidden($message);
    }

    /**
     * Validation error response (422)
     *
     * @param array $errors Validation errors
     * @param string $message Validation message
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = ''): JsonResponse
    {
        return $this->apiResponse()->validationError($errors, $message);
    }

    /**
     * Server error response (500)
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function serverErrorResponse(string $message = ''): JsonResponse
    {
        return $this->apiResponse()->serverError($message);
    }

    /**
     * Paginated response with meta information
     *
     * @param LengthAwarePaginator $paginator Paginator instance
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = ''): JsonResponse
    {
        return $this->apiResponse()->paginated($paginator, $message);
    }

    /**
     * Collection response
     *
     * @param Collection $collection Collection of items
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function collectionResponse(Collection $collection, string $message = ''): JsonResponse
    {
        return $this->apiResponse()->collection($collection, $message);
    }

    /**
     * Response with custom structure
     *
     * @param array $data Custom response data
     * @param bool $success Success flag
     * @param int $code HTTP status code
     * @return JsonResponse
     */
    protected function customResponse(array $data, bool $success = true, int $code = 200): JsonResponse
    {
        return $this->apiResponse()->custom($data, $success, $code);
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
    protected function operationResultResponse(
        bool $success,
        string $successMessage,
        string $errorMessage,
        mixed $data = null
    ): JsonResponse {
        return $this->apiResponse()->operationResult($success, $successMessage, $errorMessage, $data);
    }
}
