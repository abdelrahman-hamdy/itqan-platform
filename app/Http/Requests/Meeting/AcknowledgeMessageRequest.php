<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'message_id' => 'required|string',
            'response_data' => 'array',
        ];
    }

    public function messages(): array
    {
        return [
            'message_id.required' => 'معرف الرسالة مطلوب',
            'message_id.string' => 'معرف الرسالة يجب أن يكون نص',
            'response_data.array' => 'بيانات الاستجابة يجب أن تكون مصفوفة',
        ];
    }
}
