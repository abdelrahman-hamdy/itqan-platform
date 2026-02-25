<?php

namespace App\Http\Requests\Homework;

use App\Models\AcademicHomework;
use Illuminate\Foundation\Http\FormRequest;

class SubmitHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->isStudent();
    }

    public function rules(): array
    {
        // Allowed MIME types for homework file submissions
        $allowedMimes = 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,mp3,mp4,zip';

        // Dynamic validation based on homework submission requirements
        $rules = [];

        // Get homework from route or request
        $homeworkId = $this->route('id') ?? $this->input('homework_id');
        $type = $this->route('type') ?? 'academic';

        if ($type === 'academic' && $homeworkId) {
            $homework = AcademicHomework::find($homeworkId);

            if ($homework && $homework->submission_type) {
                if (in_array($homework->submission_type, ['text', 'both'])) {
                    $rules['text'] = 'required|string|min:10';
                }
                if (in_array($homework->submission_type, ['file', 'both'])) {
                    $maxKilobytes = isset($homework->max_file_size_mb) ? $homework->max_file_size_mb * 1024 : 10240;
                    $rules['files'] = 'required|array|min:1';
                    $rules['files.*'] = 'file|mimes:'.$allowedMimes.'|max:'.$maxKilobytes;
                }
            }
        }

        // Default validation if no specific requirements
        if (empty($rules)) {
            $rules = [
                'text' => 'required_without:files|string|min:10',
                'files' => 'required_without:text|array',
                'files.*' => 'file|mimes:'.$allowedMimes.'|max:10240',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'text.required' => 'يجب إدخال نص الإجابة',
            'text.min' => 'يجب أن يكون النص 10 أحرف على الأقل',
            'files.required' => 'يجب إرفاق ملف واحد على الأقل',
            'files.*.max' => 'حجم الملف يجب أن لا يتجاوز 10 ميجابايت',
        ];
    }
}
