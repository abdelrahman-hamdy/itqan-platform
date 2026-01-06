<?php

namespace App\Http\Requests\Api\V1\Student;

use App\Http\Requests\Api\BaseApiFormRequest;

/**
 * Form request for student profile updates.
 *
 * Validates:
 * - Personal information (name, phone, birth date, etc.)
 * - Password change (requires current password)
 */
class ProfileUpdateRequest extends BaseApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'birth_date' => ['sometimes', 'date', 'before:today'],
            'gender' => ['sometimes', 'in:male,female'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'parent_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'emergency_contact' => ['sometimes', 'nullable', 'string', 'max:255'],
            'current_password' => ['required_with:new_password', 'string'],
            'new_password' => ['sometimes', 'string', 'min:8', 'confirmed'],
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
            'first_name' => __('first name'),
            'last_name' => __('last name'),
            'phone' => __('phone number'),
            'birth_date' => __('birth date'),
            'gender' => __('gender'),
            'nationality' => __('nationality'),
            'address' => __('address'),
            'parent_phone' => __('parent phone'),
            'emergency_contact' => __('emergency contact'),
            'current_password' => __('current password'),
            'new_password' => __('new password'),
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
            'current_password.required_with' => __('Current password is required to change password.'),
            'new_password.min' => __('New password must be at least 8 characters.'),
            'new_password.confirmed' => __('Password confirmation does not match.'),
            'birth_date.before' => __('Birth date must be before today.'),
        ];
    }

    /**
     * Get the validation error message.
     */
    protected function getValidationErrorMessage(): string
    {
        return __('Profile update validation failed.');
    }
}
