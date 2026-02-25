<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Helpers\CountryList;
use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\PasswordRules;

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
     * Allow unauthenticated access — registration endpoint is for guests.
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => PasswordRules::create(),
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'in:'.CountryList::validationRule()],
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
            ...PasswordRules::messagesEn(),
            'birth_date.before' => __('Birth date must be before today.'),
            'grade_level_id.exists' => __('The selected grade level is invalid.'),
        ];
    }
}
