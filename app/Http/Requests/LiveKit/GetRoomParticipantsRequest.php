<?php

namespace App\Http\Requests\LiveKit;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;

class GetRoomParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
    }

    public function rules(): array
    {
        return [
            'room_name' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'room_name.required' => 'اسم الغرفة مطلوب',
            'room_name.string' => 'اسم الغرفة يجب أن يكون نص',
        ];
    }
}
