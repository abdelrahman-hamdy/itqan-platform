<?php

namespace App\Http\Requests\Lesson;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_time' => 'required|numeric|min:0',
            'total_time' => 'required|numeric|min:0',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'current_time.required' => 'الوقت الحالي مطلوب',
            'current_time.numeric' => 'الوقت الحالي يجب أن يكون رقم',
            'current_time.min' => 'الوقت الحالي يجب أن يكون صفر أو أكثر',
            'total_time.required' => 'الوقت الإجمالي مطلوب',
            'total_time.numeric' => 'الوقت الإجمالي يجب أن يكون رقم',
            'total_time.min' => 'الوقت الإجمالي يجب أن يكون صفر أو أكثر',
            'progress_percentage.required' => 'نسبة التقدم مطلوبة',
            'progress_percentage.numeric' => 'نسبة التقدم يجب أن تكون رقم',
            'progress_percentage.min' => 'نسبة التقدم يجب أن تكون صفر أو أكثر',
            'progress_percentage.max' => 'نسبة التقدم يجب ألا تتجاوز 100',
        ];
    }
}
