<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\Api\BaseApiFormRequest;

/**
 * Login Request
 *
 * Validates user login credentials for API authentication.
 * Extends BaseApiFormRequest for consistent error response format.
 */
class LoginRequest extends BaseApiFormRequest
{
    /**
     * Allow unauthenticated access — login endpoint is for guests.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255', 'regex:/^[\w\s\-\.\(\)]+$/'],
            'fcm_token' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('Email is required.'),
            'email.email' => __('Please enter a valid email address.'),
            'password.required' => __('Password is required.'),
            'password.min' => __('Password must be at least :min characters.'),
            'device_name.regex' => __('Device name contains invalid characters.'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => __('email'),
            'password' => __('password'),
            'device_name' => __('device name'),
            'fcm_token' => __('fcm token'),
        ];
    }
}
