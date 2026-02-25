<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StartRecordingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only teachers and admins may start session recordings.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && in_array($user->user_type, [
            UserType::QURAN_TEACHER->value,
            UserType::ACADEMIC_TEACHER->value,
            UserType::ADMIN->value,
            UserType::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:interactive_course_sessions,id',
        ];
    }

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'session_id.required' => 'معرف الجلسة مطلوب',
            'session_id.exists' => 'الجلسة المحددة غير موجودة',
        ];
    }
}
