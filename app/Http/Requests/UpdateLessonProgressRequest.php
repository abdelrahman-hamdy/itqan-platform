<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonProgressRequest extends FormRequest
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
            'current_time' => 'required|numeric|min:0',
            'total_time' => 'required|numeric|min:0',
            'progress_percentage' => 'required|numeric|min:0|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_time.required' => 'الوقت الحالي مطلوب',
            'current_time.numeric' => 'يجب أن يكون الوقت الحالي رقماً',
            'current_time.min' => 'يجب ألا يقل الوقت الحالي عن 0',
            'total_time.required' => 'إجمالي الوقت مطلوب',
            'total_time.numeric' => 'يجب أن يكون إجمالي الوقت رقماً',
            'total_time.min' => 'يجب ألا يقل إجمالي الوقت عن 0',
            'progress_percentage.required' => 'نسبة التقدم مطلوبة',
            'progress_percentage.numeric' => 'يجب أن تكون نسبة التقدم رقماً',
            'progress_percentage.min' => 'يجب ألا تقل نسبة التقدم عن 0',
            'progress_percentage.max' => 'يجب ألا تتجاوز نسبة التقدم 100',
        ];
    }
}
