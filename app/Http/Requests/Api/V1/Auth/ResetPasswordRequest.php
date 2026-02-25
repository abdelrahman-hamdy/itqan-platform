<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\PasswordRules;

/**
 * Form request for password reset.
 *
 * Validates:
 * - Reset token
 * - Email address
 * - New password (min 12 chars, mixed case, numbers)
 */
class ResetPasswordRequest extends BaseApiFormRequest
{
    /**
     * Allow unauthenticated access — password reset endpoint is for guests.
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
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => PasswordRules::reset(),
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
            'token' => __('reset token'),
            'email' => __('email address'),
            'password' => __('new password'),
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
            'token.required' => __('Reset token is required.'),
            ...PasswordRules::messagesEn(),
        ];
    }

    /**
     * Get the validation error message.
     */
    protected function getValidationErrorMessage(): string
    {
        return __('Password reset validation failed.');
    }
}
