<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class AddQuranSessionFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'student_feedback' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'student_feedback.required' => 'التقييم مطلوب',
            'student_feedback.string' => 'التقييم يجب أن يكون نص',
            'student_feedback.max' => 'التقييم يجب ألا يتجاوز 1000 حرف',
            'rating.required' => 'التقييم بالنجوم مطلوب',
            'rating.integer' => 'التقييم بالنجوم يجب أن يكون رقم',
            'rating.min' => 'التقييم بالنجوم يجب أن يكون على الأقل 1',
            'rating.max' => 'التقييم بالنجوم يجب ألا يتجاوز 5',
        ];
    }
}
