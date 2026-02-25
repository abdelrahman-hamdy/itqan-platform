<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GrantMicrophoneToStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only teachers and admins may grant microphone permissions.
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
            'student_id' => 'required|exists:users,id',
        ];
    }
}
