<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTeacherCommandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
