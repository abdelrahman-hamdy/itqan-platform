<?php

namespace App\Http\Requests;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicSessionStatusRequest extends FormRequest
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
            $session = AcademicSession::find($sessionParam);
        } else {
            $session = $sessionParam;
        }

        // Check if this teacher owns the session
        return $session && (int) $session->academic_teacher_id === (int) $teacherProfile->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:scheduled,ongoing,completed,cancelled,rescheduled',
            'attendance_status' => 'nullable|in:scheduled,attended,absent,late,left',
            'attendance_notes' => 'nullable|string|max:500',
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
            'status.required' => 'حالة الجلسة مطلوبة',
            'status.in' => 'حالة الجلسة غير صالحة',
            'attendance_status.in' => 'حالة الحضور غير صالحة',
            'attendance_notes.max' => 'ملاحظات الحضور يجب ألا تتجاوز 500 حرف',
        ];
    }
}
