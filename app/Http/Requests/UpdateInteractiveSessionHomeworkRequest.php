<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInteractiveSessionHomeworkRequest extends FormRequest
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
            'homework_description' => 'required|string|max:2000',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'homework_description.required' => 'وصف الواجب مطلوب',
            'homework_description.max' => 'وصف الواجب يجب ألا يتجاوز 2000 حرف',
            'homework_file.file' => 'الملف المرفق يجب أن يكون ملفاً صحيحاً',
            'homework_file.mimes' => 'الملف المرفق يجب أن يكون من نوع: pdf, doc, docx',
            'homework_file.max' => 'حجم الملف المرفق يجب ألا يتجاوز 10 ميجابايت',
        ];
    }
}
