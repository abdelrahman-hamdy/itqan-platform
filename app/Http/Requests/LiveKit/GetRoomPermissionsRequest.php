<?php

namespace App\Http\Requests\LiveKit;

use Illuminate\Foundation\Http\FormRequest;

class GetRoomPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
