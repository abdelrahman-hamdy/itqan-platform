<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['sometimes', 'string', 'max:255'],
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
        ];
    }

    /**
     * Handle a failed validation attempt.
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
            'message' => __('Validation failed'),
            'error_code' => 'VALIDATION_ERROR',
            'errors' => $validator->errors(),
            'meta' => [
                'timestamp' => now()->toISOString(),
                'api_version' => 'v1',
            ],
        ], 422));
    }
}
