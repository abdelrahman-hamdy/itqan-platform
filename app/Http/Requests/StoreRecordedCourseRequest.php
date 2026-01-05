<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordedCourseRequest extends FormRequest
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

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الدورة مطلوب',
            'title.string' => 'يجب أن يكون عنوان الدورة نصاً',
            'title.max' => 'يجب ألا يتجاوز عنوان الدورة 255 حرفاً',
            'description.required' => 'وصف الدورة مطلوب',
            'description.string' => 'يجب أن يكون وصف الدورة نصاً',

            'subject_id.exists' => 'المادة المحددة غير موجودة',
            'grade_level_id.exists' => 'المستوى الدراسي المحدد غير موجود',

            'price.required' => 'سعر الدورة مطلوب',
            'price.numeric' => 'يجب أن يكون السعر رقماً',
            'price.min' => 'يجب ألا يقل السعر عن 0',

            'prerequisites.array' => 'يجب أن تكون المتطلبات الأساسية مصفوفة',
            'learning_outcomes.array' => 'يجب أن تكون نواتج التعلم مصفوفة',
            'tags.array' => 'يجب أن تكون الوسوم مصفوفة',
            'difficulty_level.required' => 'مستوى الصعوبة مطلوب',
            'difficulty_level.in' => 'مستوى الصعوبة يجب أن يكون: سهل، متوسط، أو صعب',
            'thumbnail_url.url' => 'يجب أن يكون رابط الصورة المصغرة صحيحاً',
        ];
    }
}
