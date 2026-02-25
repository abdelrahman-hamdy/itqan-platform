<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendTeacherCommandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only teachers and admins may issue teacher commands.
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
            'command' => 'required|string|in:mute_all_students,allow_student_microphones,clear_all_hand_raises,grant_microphone_permission,end_session,kick_participant',
            'data' => 'array',
            'targets' => 'array',
        ];
    }
}
