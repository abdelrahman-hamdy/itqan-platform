<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartRecordingRequest extends FormRequest
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
            'session_id' => 'required|exists:interactive_course_sessions,id',
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
            'session_id.required' => 'معرف الجلسة مطلوب',
            'session_id.exists' => 'الجلسة المحددة غير موجودة',
        ];
    }
}
