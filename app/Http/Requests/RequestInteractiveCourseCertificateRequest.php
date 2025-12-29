<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestInteractiveCourseCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated and is a student
        if (! $this->user() || ! $this->user()->isStudent()) {
            return false;
        }

        // Get the enrollment
        $enrollmentId = $this->input('enrollment_id');
        if (! $enrollmentId) {
            return false;
        }

        $enrollment = \App\Models\InteractiveCourseEnrollment::find($enrollmentId);

        // Check if student owns this enrollment
        return $enrollment && $enrollment->student_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enrollment_id' => 'required|exists:interactive_course_enrollments,id',
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
            'enrollment_id.required' => 'معرف التسجيل مطلوب',
            'enrollment_id.exists' => 'التسجيل غير موجود',
        ];
    }
}
