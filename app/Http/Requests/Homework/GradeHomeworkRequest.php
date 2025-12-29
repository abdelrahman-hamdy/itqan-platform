<?php

namespace App\Http\Requests\Homework;

use Illuminate\Foundation\Http\FormRequest;

class GradeHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $submission = $this->route('submissionId')
            ? \App\Models\AcademicHomeworkSubmission::find($this->route('submissionId'))
            : null;

        $maxScore = $submission ? $submission->max_score : 100;

        return [
            'score' => 'required|numeric|min:0|max:' . $maxScore,
            'teacher_feedback' => 'required|string|min:10',
            'content_quality_score' => 'nullable|numeric|min:0|max:100',
            'presentation_score' => 'nullable|numeric|min:0|max:100',
            'effort_score' => 'nullable|numeric|min:0|max:100',
            'creativity_score' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => 'يجب إدخال الدرجة',
            'score.max' => 'الدرجة يجب أن لا تتجاوز الدرجة القصوى',
            'teacher_feedback.required' => 'يجب كتابة ملاحظات وتعليقات',
            'teacher_feedback.min' => 'يجب أن تكون الملاحظات 10 أحرف على الأقل',
            'content_quality_score.numeric' => 'درجة جودة المحتوى يجب أن تكون رقم',
            'content_quality_score.max' => 'درجة جودة المحتوى يجب ألا تتجاوز 100',
            'presentation_score.numeric' => 'درجة العرض يجب أن تكون رقم',
            'presentation_score.max' => 'درجة العرض يجب ألا تتجاوز 100',
            'effort_score.numeric' => 'درجة الجهد يجب أن تكون رقم',
            'effort_score.max' => 'درجة الجهد يجب ألا تتجاوز 100',
            'creativity_score.numeric' => 'درجة الإبداع يجب أن تكون رقم',
            'creativity_score.max' => 'درجة الإبداع يجب ألا تتجاوز 100',
        ];
    }
}
