<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignHomeworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAcademicTeacher() || $user->isQuranTeacher());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'homework_description' => 'required|string|max:2000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'due_date' => 'nullable|date|after:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'homework_description.required' => 'وصف الواجب مطلوب',
            'homework_description.max' => 'وصف الواجب يجب ألا يتجاوز 2000 حرف',
            'homework_file.file' => 'الملف غير صالح',
            'homework_file.mimes' => 'الملف يجب أن يكون من نوع PDF أو Word',
            'homework_file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
            'due_date.date' => 'تاريخ التسليم غير صالح',
            'due_date.after' => 'تاريخ التسليم يجب أن يكون بعد اليوم',
        ];
    }
}
