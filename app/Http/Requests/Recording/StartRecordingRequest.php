<?php

namespace App\Http\Requests\Recording;

use Illuminate\Foundation\Http\FormRequest;

class StartRecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAcademicTeacher();
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:interactive_course_sessions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'معرف الجلسة مطلوب',
            'session_id.exists' => 'الجلسة غير موجودة',
        ];
    }
}
