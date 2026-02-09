<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;

class PreviewCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only teachers and admins can preview certificates
        return $this->user() && $this->user()->hasAnyRole(['teacher', UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::ADMIN->value, UserType::SUPER_ADMIN->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_name' => 'required|string',
            'certificate_text' => 'required|string',
            'teacher_name' => 'nullable|string',
            'academy_name' => 'required|string',
            'template_style' => 'required|string',
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
            'student_name.required' => 'اسم الطالب مطلوب',
            'student_name.string' => 'اسم الطالب يجب أن يكون نصاً',
            'certificate_text.required' => 'نص الشهادة مطلوب',
            'certificate_text.string' => 'نص الشهادة يجب أن يكون نصاً',
            'teacher_name.string' => 'اسم المعلم يجب أن يكون نصاً',
            'academy_name.required' => 'اسم الأكاديمية مطلوب',
            'academy_name.string' => 'اسم الأكاديمية يجب أن يكون نصاً',
            'template_style.required' => 'نمط القالب مطلوب',
            'template_style.string' => 'نمط القالب يجب أن يكون نصاً',
        ];
    }
}
