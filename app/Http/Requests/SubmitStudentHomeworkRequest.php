<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitStudentHomeworkRequest extends FormRequest
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
        // Get homework from route to determine validation rules
        $homeworkId = $this->route('id');
        $type = $this->route('type') ?? 'academic';

        $validationRules = [];

        // Dynamic validation based on homework type and requirements
        if ($type === 'academic') {
            // Get homework to check submission type (would need to be injected)
            // For now, use flexible validation
            $validationRules['text'] = 'required_without:files|string|min:10';
            $validationRules['files'] = 'required_without:text|array';
            $validationRules['files.*'] = 'file|max:10240'; // 10MB
        } else {
            // Default validation for other types
            $validationRules['text'] = 'required_without:files|string|min:10';
            $validationRules['files'] = 'required_without:text|array';
            $validationRules['files.*'] = 'file|max:10240'; // 10MB
        }

        return $validationRules;
    }

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.required' => 'يجب إدخال نص الإجابة',
            'text.required_without' => 'يجب إدخال نص الإجابة أو إرفاق ملف',
            'text.string' => 'يجب أن يكون النص نصاً',
            'text.min' => 'يجب أن يكون النص 10 أحرف على الأقل',
            'files.required' => 'يجب إرفاق ملف واحد على الأقل',
            'files.required_without' => 'يجب إرفاق ملف واحد على الأقل أو إدخال نص الإجابة',
            'files.array' => 'يجب أن تكون الملفات مصفوفة',
            'files.*.file' => 'يجب أن يكون كل عنصر ملفاً صحيحاً',
            'files.*.max' => 'حجم الملف يجب أن لا يتجاوز 10 ميجابايت',
        ];
    }
}
