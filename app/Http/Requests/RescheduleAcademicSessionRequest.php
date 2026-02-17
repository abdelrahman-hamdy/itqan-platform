<?php

namespace App\Http\Requests;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleAcademicSessionRequest extends FormRequest
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
            'new_scheduled_at' => 'required|date|after:now',
            'reschedule_reason' => 'required|string|max:500',
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
            'new_scheduled_at.required' => 'الموعد الجديد مطلوب',
            'new_scheduled_at.date' => 'الموعد الجديد غير صالح',
            'new_scheduled_at.after' => 'الموعد الجديد يجب أن يكون في المستقبل',
            'reschedule_reason.required' => 'سبب إعادة الجدولة مطلوب',
            'reschedule_reason.max' => 'سبب إعادة الجدولة يجب ألا يتجاوز 500 حرف',
        ];
    }
}
