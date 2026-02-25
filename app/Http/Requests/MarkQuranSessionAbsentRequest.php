<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkQuranSessionAbsentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only teachers and admins may mark students absent.
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
            'reason' => 'nullable|string|max:500',
        ];
    }
}
