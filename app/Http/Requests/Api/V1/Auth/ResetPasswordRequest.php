<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rules\Password;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
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
            'password.min' => __('Password must be at least 12 characters.'),
            'password.letters' => __('Password must contain at least one letter.'),
            'password.mixed' => __('Password must contain both uppercase and lowercase letters.'),
            'password.numbers' => __('Password must contain at least one number.'),
            'password.confirmed' => __('Password confirmation does not match.'),
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
