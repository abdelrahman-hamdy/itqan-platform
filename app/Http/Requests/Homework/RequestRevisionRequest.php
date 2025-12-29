<?php

namespace App\Http\Requests\Homework;

use Illuminate\Foundation\Http\FormRequest;

class RequestRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'revision_reason' => 'required|string|min:10',
        ];
    }

    public function messages(): array
    {
        return [
            'revision_reason.required' => 'يجب كتابة سبب طلب التعديل',
            'revision_reason.min' => 'يجب أن يكون السبب 10 أحرف على الأقل',
        ];
    }
}
