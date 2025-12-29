<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicHomeworkRequest extends FormRequest
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
        return $session && $session->academic_teacher_id === $teacherProfile->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'homework_description' => 'required|string|max:2000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
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
            'homework_description.required' => 'يجب إدخال وصف الواجب',
            'homework_description.max' => 'وصف الواجب يجب ألا يتجاوز 2000 حرف',
            'homework_file.file' => 'يجب أن يكون المرفق ملفاً صالحاً',
            'homework_file.mimes' => 'يجب أن يكون الملف من نوع: PDF أو Word',
            'homework_file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
        ];
    }
}
