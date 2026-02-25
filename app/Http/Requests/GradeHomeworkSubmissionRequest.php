<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GradeHomeworkSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only teachers may grade homework submissions.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isQuranTeacher() || $user->isAcademicTeacher());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get submission from route to get max score dynamically
        $submissionId = $this->route('submissionId');

        // For now, use a reasonable default max score
        // The controller can validate against actual max_score
        return [
            'score' => 'required|numeric|min:0',
            'teacher_feedback' => 'required|string|min:10',
            'content_quality_score' => 'nullable|numeric|min:0|max:100',
            'presentation_score' => 'nullable|numeric|min:0|max:100',
            'effort_score' => 'nullable|numeric|min:0|max:100',
            'creativity_score' => 'nullable|numeric|min:0|max:100',
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
            'score.required' => 'يجب إدخال الدرجة',
            'score.numeric' => 'يجب أن تكون الدرجة رقماً',
            'score.min' => 'يجب ألا تقل الدرجة عن 0',
            'teacher_feedback.required' => 'يجب كتابة ملاحظات وتعليقات',
            'teacher_feedback.string' => 'يجب أن تكون الملاحظات نصاً',
            'teacher_feedback.min' => 'يجب أن تكون الملاحظات 10 أحرف على الأقل',
            'content_quality_score.numeric' => 'يجب أن تكون درجة جودة المحتوى رقماً',
            'content_quality_score.min' => 'يجب ألا تقل درجة جودة المحتوى عن 0',
            'content_quality_score.max' => 'يجب ألا تتجاوز درجة جودة المحتوى 100',
            'presentation_score.numeric' => 'يجب أن تكون درجة العرض رقماً',
            'presentation_score.min' => 'يجب ألا تقل درجة العرض عن 0',
            'presentation_score.max' => 'يجب ألا تتجاوز درجة العرض 100',
            'effort_score.numeric' => 'يجب أن تكون درجة الجهد رقماً',
            'effort_score.min' => 'يجب ألا تقل درجة الجهد عن 0',
            'effort_score.max' => 'يجب ألا تتجاوز درجة الجهد 100',
            'creativity_score.numeric' => 'يجب أن تكون درجة الإبداع رقماً',
            'creativity_score.min' => 'يجب ألا تقل درجة الإبداع عن 0',
            'creativity_score.max' => 'يجب ألا تتجاوز درجة الإبداع 100',
        ];
    }
}
