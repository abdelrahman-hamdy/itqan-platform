<?php

namespace App\Http\Requests\Lesson;

use Illuminate\Foundation\Http\FormRequest;

class AddNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'note' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'الملاحظة مطلوبة',
            'note.string' => 'الملاحظة يجب أن تكون نص',
            'note.max' => 'الملاحظة يجب ألا تتجاوز 1000 حرف',
        ];
    }
}
