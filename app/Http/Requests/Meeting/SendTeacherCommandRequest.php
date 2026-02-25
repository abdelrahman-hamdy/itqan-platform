<?php

namespace App\Http\Requests\Meeting;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;

class SendTeacherCommandRequest extends FormRequest
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
            'command' => 'required|string|in:mute_all_students,allow_student_microphones,clear_all_hand_raises,grant_microphone_permission,end_session,kick_participant',
            'data' => 'array',
            'targets' => 'array',
        ];
    }

    public function messages(): array
    {
        return [
            'command.required' => 'الأمر مطلوب',
            'command.string' => 'الأمر يجب أن يكون نص',
            'command.in' => 'الأمر غير صحيح',
            'data.array' => 'البيانات يجب أن تكون مصفوفة',
            'targets.array' => 'الأهداف يجب أن تكون مصفوفة',
        ];
    }
}
