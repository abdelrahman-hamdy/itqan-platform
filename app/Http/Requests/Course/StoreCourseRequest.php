<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'required|string',
            'description_en' => 'nullable|string',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'grade_level_id' => 'nullable|exists:academic_grade_levels,id',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'category' => 'nullable|string|max:100',
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
            'discount_price.numeric' => 'سعر الخصم يجب أن يكون رقم',
            'discount_price.lt' => 'سعر الخصم يجب أن يكون أقل من السعر الأصلي',
            'difficulty_level.required' => 'مستوى الصعوبة مطلوب',
            'difficulty_level.in' => 'مستوى الصعوبة غير صحيح',
            'thumbnail_url.url' => 'رابط الصورة المصغرة غير صحيح',
        ];
    }
}
