<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuranSessionNotesRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lesson_content' => 'nullable|string|max:5000',
            'teacher_notes' => 'nullable|string|max:2000',
            'student_progress' => 'nullable|string|max:1000',
            'homework_assigned' => 'nullable|string|max:1000',
        ];
    }
}
