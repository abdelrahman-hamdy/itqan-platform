<?php

namespace App\Http\Requests\LiveKit;

use Illuminate\Foundation\Http\FormRequest;

class MuteParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->user_type, ['quran_teacher', 'academic_teacher']);
    }

    public function rules(): array
    {
        return [
            'room_name' => 'required|string',
            'participant_identity' => 'required|string',
            'track_sid' => 'required|string',
            'muted' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'room_name.required' => 'اسم الغرفة مطلوب',
            'participant_identity.required' => 'معرف المشارك مطلوب',
            'track_sid.required' => 'معرف المسار مطلوب',
            'muted.required' => 'حالة كتم الصوت مطلوبة',
            'muted.boolean' => 'حالة كتم الصوت يجب أن تكون true أو false',
        ];
    }
}
