<?php

namespace App\Http\Requests\Meeting;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;

class GrantMicrophoneRequest extends FormRequest
{
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

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'معرف الطالب مطلوب',
            'student_id.exists' => 'الطالب غير موجود',
        ];
    }
}
