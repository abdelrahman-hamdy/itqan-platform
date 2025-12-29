<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLiveKitTokenRequest extends FormRequest
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
            'room_name' => 'required|string',
            'participant_name' => 'required|string',
            'user_type' => 'required|string|in:quran_teacher,student',
        ];
    }
}
