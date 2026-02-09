<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;

class GradeAcademicHomeworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated and is an academic teacher
        if (! $this->user() || $this->user()->user_type !== UserType::ACADEMIC_TEACHER->value) {
            return false;
        }

        // Get the teacher profile
        $teacherProfile = $this->user()->academicTeacherProfile;
        if (! $teacherProfile) {
            return false;
        }

        // Get the session from route parameter
        $session = $this->route('session');

        // Check if this teacher owns the session
        return $session && $session->academic_teacher_id === $teacherProfile->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'homework_grade' => [
                'required',
                'numeric',
                'min:0',
                'max:10',
            ],
            'homework_feedback' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'homework_grade.required' => 'يجب إدخال درجة الواجب',
            'homework_grade.numeric' => 'يجب أن تكون الدرجة رقماً',
            'homework_grade.min' => 'الدرجة يجب ألا تقل عن 0',
            'homework_grade.max' => 'الدرجة يجب ألا تزيد عن 10',
            'homework_feedback.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
        ];
    }
}
