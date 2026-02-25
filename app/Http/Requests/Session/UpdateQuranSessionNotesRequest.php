<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuranSessionNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isQuranTeacher() || $user->isAcademicTeacher() || $user->isAdmin() || $user->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'lesson_content' => 'nullable|string|max:5000',
            'teacher_notes' => 'nullable|string|max:2000',
            'student_progress' => 'nullable|string|max:1000',
            'homework_assigned' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'lesson_content.string' => 'محتوى الدرس يجب أن يكون نص',
            'lesson_content.max' => 'محتوى الدرس يجب ألا يتجاوز 5000 حرف',
            'teacher_notes.string' => 'ملاحظات المعلم يجب أن تكون نص',
            'teacher_notes.max' => 'ملاحظات المعلم يجب ألا تتجاوز 2000 حرف',
            'student_progress.string' => 'تقدم الطالب يجب أن يكون نص',
            'student_progress.max' => 'تقدم الطالب يجب ألا يتجاوز 1000 حرف',
            'homework_assigned.string' => 'الواجب المنزلي يجب أن يكون نص',
            'homework_assigned.max' => 'الواجب المنزلي يجب ألا يتجاوز 1000 حرف',
        ];
    }
}
