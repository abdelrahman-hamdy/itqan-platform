<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;

class SubmitAcademicHomeworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated and is a student
        if (! $this->user() || $this->user()->user_type !== UserType::STUDENT->value) {
            return false;
        }

        // Get the session from route parameter
        $session = $this->route('session');

        // Check if student is enrolled in this session
        return $session && $session->student_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'homework_file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'max:10240', // 10MB max
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'homework_file.required' => 'يجب رفع ملف الواجب',
            'homework_file.file' => 'يجب أن يكون المرفق ملفاً صالحاً',
            'homework_file.mimes' => 'يجب أن يكون الملف من نوع: PDF, Word, أو صورة',
            'homework_file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
        ];
    }
}
