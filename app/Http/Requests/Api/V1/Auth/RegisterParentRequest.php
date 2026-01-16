<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Form request for parent registration.
 *
 * Validates:
 * - Personal information (name, email, phone)
 * - Password requirements (min 12 chars, mixed case, numbers)
 * - Student code for linking
 * - Relationship type (father, mother, guardian, other)
 */
class RegisterParentRequest extends BaseApiFormRequest
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
            'student_code' => ['required', 'string'],
            'relationship_type' => ['required', 'in:father,mother,guardian,other'],
            'preferred_contact_method' => ['sometimes', 'in:phone,email,sms,whatsapp'],
            'occupation' => ['nullable', 'string', 'max:255'],
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
            'student_code' => __('student code'),
            'relationship_type' => __('relationship type'),
            'preferred_contact_method' => __('preferred contact method'),
            'occupation' => __('occupation'),
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
            'student_code.required' => __('Student code is required to link your account.'),
            'relationship_type.in' => __('Please select a valid relationship type.'),
        ];
    }
}
