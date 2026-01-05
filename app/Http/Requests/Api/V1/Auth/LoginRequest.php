<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Login Request
 *
 * Validates user login credentials for API authentication.
 * Extends BaseApiFormRequest for consistent error response format.
 */
class LoginRequest extends BaseApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
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
            'email.required' => __('البريد الإلكتروني مطلوب'),
            'email.email' => __('يرجى إدخال بريد إلكتروني صالح'),
            'password.required' => __('كلمة المرور مطلوبة'),
            'password.min' => __('كلمة المرور يجب أن تكون :min أحرف على الأقل'),
            'password.letters' => __('كلمة المرور يجب أن تحتوي على أحرف'),
            'password.numbers' => __('كلمة المرور يجب أن تحتوي على أرقام'),
            'device_name.regex' => __('اسم الجهاز يحتوي على أحرف غير صالحة'),
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
            'email' => __('البريد الإلكتروني'),
            'password' => __('كلمة المرور'),
            'device_name' => __('اسم الجهاز'),
            'fcm_token' => __('رمز الإشعارات'),
        ];
    }
}
