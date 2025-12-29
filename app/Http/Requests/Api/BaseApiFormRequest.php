<?php

namespace App\Http\Requests\Api;

use App\Enums\Api\ErrorCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

/**
 * Base API Form Request
 *
 * All API form requests should extend this class to ensure consistent
 * validation error responses across the API.
 *
 * Features:
 * - Standardized validation error format
 * - Includes request_id in meta for debugging
 * - Uses ErrorCode enum for consistent error codes
 * - Localized error messages
 *
 * @see app/Enums/Api/ErrorCode.php
 */
abstract class BaseApiFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Override in child classes if authorization is needed.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    abstract public function rules(): array;

    /**
     * Handle a failed validation attempt.
     *
     * Throws an HttpResponseException with a standardized API error response.
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $this->getValidationErrorMessage(),
            'error_code' => ErrorCode::VALIDATION_ERROR->value,
            'errors' => $this->formatValidationErrors($validator),
            'meta' => $this->getApiMeta(),
        ], 422));
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $this->getAuthorizationErrorMessage(),
            'error_code' => ErrorCode::FORBIDDEN->value,
            'errors' => [],
            'meta' => $this->getApiMeta(),
        ], 403));
    }

    /**
     * Get the validation error message.
     * Override in child classes to customize.
     */
    protected function getValidationErrorMessage(): string
    {
        return __('Validation failed');
    }

    /**
     * Get the authorization error message.
     * Override in child classes to customize.
     */
    protected function getAuthorizationErrorMessage(): string
    {
        return __('You are not authorized to perform this action.');
    }

    /**
     * Format validation errors for API response.
     *
     * Returns errors in a format suitable for API consumers:
     * {
     *   "field_name": ["Error message 1", "Error message 2"],
     *   "nested.field": ["Error message"]
     * }
     *
     * @param  Validator  $validator
     * @return array
     */
    protected function formatValidationErrors(Validator $validator): array
    {
        return $validator->errors()->toArray();
    }

    /**
     * Get API metadata for response.
     *
     * @return array
     */
    protected function getApiMeta(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'request_id' => $this->header('X-Request-ID', (string) Str::uuid()),
            'api_version' => $this->getApiVersion(),
        ];
    }

    /**
     * Get the API version from the request path.
     *
     * @return string
     */
    protected function getApiVersion(): string
    {
        // Extract version from path like /api/v1/... or /api/v2/...
        $path = $this->path();
        if (preg_match('/api\/(v\d+)/', $path, $matches)) {
            return $matches[1];
        }

        return 'v1';
    }

    /**
     * Get custom attributes for validator errors.
     * Override in child classes to provide localized attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get custom messages for validator errors.
     * Override in child classes to provide custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Prepare the data for validation.
     * Override in child classes to sanitize or transform input.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Override in child classes
    }

    /**
     * Handle a passed validation attempt.
     * Override in child classes to perform post-validation processing.
     *
     * @return void
     */
    protected function passedValidation(): void
    {
        // Override in child classes
    }
}
