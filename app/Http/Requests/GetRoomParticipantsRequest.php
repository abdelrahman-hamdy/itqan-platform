<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;

class GetRoomParticipantsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && in_array($user->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_name' => 'nullable|string',
        ];
    }
}
