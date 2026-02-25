<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddLessonNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only teachers and students may add lesson notes.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->isQuranTeacher() ||
            $user->isAcademicTeacher() ||
            $user->isStudent()
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => 'required|string|max:1000',
        ];
    }
}
