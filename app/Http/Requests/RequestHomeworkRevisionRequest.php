<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestHomeworkRevisionRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'revision_reason' => 'required|string|min:10',
        ];
    }

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'revision_reason.required' => 'يجب كتابة سبب طلب التعديل',
            'revision_reason.string' => 'يجب أن يكون سبب طلب التعديل نصاً',
            'revision_reason.min' => 'يجب أن يكون السبب 10 أحرف على الأقل',
        ];
    }
}
