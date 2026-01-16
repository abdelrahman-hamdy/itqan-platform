<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Form request for student registration.
 *
 * Validates:
 * - Personal information (name, email, phone)
 * - Password requirements (min 12 chars, mixed case, numbers)
 * - Birth date and gender
 * - Grade level association
 */
class RegisterStudentRequest extends BaseApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'grade_level_id' => ['required', 'exists:academic_grade_levels,id'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
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
            'email' => __('email address'),
            'phone' => __('phone number'),
            'password' => __('password'),
            'birth_date' => __('birth date'),
            'gender' => __('gender'),
            'nationality' => __('nationality'),
            'grade_level_id' => __('grade level'),
            'parent_phone' => __('parent phone'),
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
            'password.min' => __('Password must be at least 8 characters.'),
            'password.letters' => __('Password must contain at least one letter.'),
            'password.numbers' => __('Password must contain at least one number.'),
            'birth_date.before' => __('Birth date must be before today.'),
            'grade_level_id.exists' => __('The selected grade level is invalid.'),
        ];
    }
}
