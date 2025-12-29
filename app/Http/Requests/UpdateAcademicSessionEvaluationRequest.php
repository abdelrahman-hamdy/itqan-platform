<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicSessionEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated and is an academic teacher
        if (! $this->user() || ! $this->user()->isAcademicTeacher()) {
            return false;
        }

        // Get the teacher profile
        $teacherProfile = $this->user()->academicTeacherProfile;
        if (! $teacherProfile) {
            return false;
        }

        // Get the session from route parameter
        $sessionParam = $this->route('session');

        // If it's a string (ID), load the model
        if (is_string($sessionParam) || is_numeric($sessionParam)) {
            $session = \App\Models\AcademicSession::find($sessionParam);
        } else {
            $session = $sessionParam;
        }

        // Check if this teacher owns the session
        return $session && (int) $session->academic_teacher_id === (int) $teacherProfile->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_topics_covered' => 'nullable|string|max:1000',
            'lesson_content' => 'nullable|string|max:2000',
            'homework_description' => 'nullable|string|max:1000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'session_notes' => 'nullable|string|max:1000',
            'teacher_feedback' => 'nullable|string|max:1000',
            'overall_rating' => 'nullable|integer|min:1|max:5',
            'technical_issues' => 'nullable|string|max:500',
            'follow_up_required' => 'boolean',
            'follow_up_notes' => 'nullable|string|max:500',
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
            'session_topics_covered.max' => 'المواضيع المغطاة يجب ألا تتجاوز 1000 حرف',
            'lesson_content.max' => 'محتوى الدرس يجب ألا يتجاوز 2000 حرف',
            'homework_description.max' => 'وصف الواجب يجب ألا يتجاوز 1000 حرف',
            'homework_file.file' => 'يجب أن يكون المرفق ملفاً صالحاً',
            'homework_file.mimes' => 'يجب أن يكون الملف من نوع: PDF, Word, أو TXT',
            'homework_file.max' => 'حجم الملف يجب ألا يتجاوز 5 ميجابايت',
            'session_notes.max' => 'ملاحظات الجلسة يجب ألا تتجاوز 1000 حرف',
            'teacher_feedback.max' => 'ملاحظات المعلم يجب ألا تتجاوز 1000 حرف',
            'overall_rating.integer' => 'التقييم العام يجب أن يكون رقماً صحيحاً',
            'overall_rating.min' => 'التقييم العام يجب أن يكون على الأقل 1',
            'overall_rating.max' => 'التقييم العام يجب ألا يتجاوز 5',
            'technical_issues.max' => 'المشاكل التقنية يجب ألا تتجاوز 500 حرف',
            'follow_up_required.boolean' => 'قيمة المتابعة المطلوبة غير صالحة',
            'follow_up_notes.max' => 'ملاحظات المتابعة يجب ألا تتجاوز 500 حرف',
        ];
    }
}
