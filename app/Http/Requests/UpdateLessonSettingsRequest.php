<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->isAcademicTeacher();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'default_duration_minutes' => 'nullable|integer|min:30|max:180',
            'preferred_times' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'teacher_notes' => 'nullable|string|max:1000',
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
            'default_duration_minutes.integer' => 'مدة الجلسة الافتراضية يجب أن تكون رقماً صحيحاً',
            'default_duration_minutes.min' => 'مدة الجلسة الافتراضية يجب أن تكون 30 دقيقة على الأقل',
            'default_duration_minutes.max' => 'مدة الجلسة الافتراضية يجب ألا تتجاوز 180 دقيقة',
            'preferred_times.array' => 'الأوقات المفضلة يجب أن تكون مصفوفة',
            'notes.string' => 'الملاحظات يجب أن تكون نصاً',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
            'teacher_notes.string' => 'ملاحظات المعلم يجب أن تكون نصاً',
            'teacher_notes.max' => 'ملاحظات المعلم يجب ألا تتجاوز 1000 حرف',
        ];
    }
}
