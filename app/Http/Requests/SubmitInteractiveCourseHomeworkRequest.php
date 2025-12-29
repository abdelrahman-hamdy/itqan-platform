<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitInteractiveCourseHomeworkRequest extends FormRequest
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
            'homework_id' => 'required|exists:interactive_course_homework,id',
            'answer_text' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'homework_id.required' => 'معرف الواجب مطلوب',
            'homework_id.exists' => 'الواجب المحدد غير موجود',
            'files.*.file' => 'الملف المرفق يجب أن يكون ملفاً صحيحاً',
            'files.*.max' => 'حجم الملف المرفق يجب ألا يتجاوز 10 ميجابايت',
        ];
    }
}
