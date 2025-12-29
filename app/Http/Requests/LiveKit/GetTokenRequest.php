<?php

namespace App\Http\Requests\LiveKit;

use Illuminate\Foundation\Http\FormRequest;

class GetTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'room_name' => 'required|string',
            'participant_name' => 'required|string',
            'user_type' => 'required|string|in:quran_teacher,student',
        ];
    }

    public function messages(): array
    {
        return [
            'room_name.required' => 'اسم الغرفة مطلوب',
            'room_name.string' => 'اسم الغرفة يجب أن يكون نص',
            'participant_name.required' => 'اسم المشارك مطلوب',
            'participant_name.string' => 'اسم المشارك يجب أن يكون نص',
            'user_type.required' => 'نوع المستخدم مطلوب',
            'user_type.in' => 'نوع المستخدم يجب أن يكون معلم قرآن أو طالب',
        ];
    }
}
