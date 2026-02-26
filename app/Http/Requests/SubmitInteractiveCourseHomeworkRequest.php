<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitInteractiveCourseHomeworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only students may submit interactive course homework.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isStudent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'homework_id' => ['required', Rule::exists('interactive_course_homework', 'id')->where('academy_id', $this->user()?->academy_id)],
            'answer_text' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,gif,webp,mp3,wav,mp4,webm,mov', // 10MB max
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
