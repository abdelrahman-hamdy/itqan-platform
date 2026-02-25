<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAcademicTeacher() || $user->isAdmin() || $user->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'grade_level_id' => 'nullable|exists:academic_grade_levels,id',
            'price' => 'required|numeric|min:0',
            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'tags' => 'nullable|array',
            'difficulty_level' => 'required|in:easy,medium,hard',
            'thumbnail_url' => 'nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الدورة مطلوب',
            'title.max' => 'عنوان الدورة يجب ألا يتجاوز 255 حرف',
            'description.required' => 'وصف الدورة مطلوب',
            'subject_id.exists' => 'المادة غير موجودة',
            'grade_level_id.exists' => 'المستوى الدراسي غير موجود',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'price.min' => 'السعر يجب أن يكون صفر أو أكثر',
            'difficulty_level.required' => 'مستوى الصعوبة مطلوب',
            'difficulty_level.in' => 'مستوى الصعوبة غير صحيح',
            'thumbnail_url.url' => 'رابط الصورة المصغرة غير صحيح',
        ];
    }
}
