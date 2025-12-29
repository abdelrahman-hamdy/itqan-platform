<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuranCircleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->isSupervisor());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quran_teacher_id' => 'required|exists:users,id',
            'name_ar' => 'required|string|max:100',
            'name_en' => 'nullable|string|max:100',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'level' => 'required|in:beginner,elementary,intermediate,advanced,expert',
            'target_age_group' => 'required|in:children,youth,adults,all_ages',
            'min_age' => 'required|integer|min:5|max:80',
            'max_age' => 'required|integer|min:5|max:80|gte:min_age',
            'max_students' => 'required|integer|min:3|max:100',
            'price_per_student' => 'required|numeric|min:0|max:300',
            'billing_cycle' => 'required|in:weekly,monthly,quarterly,yearly',
            'day_of_week' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|in:30,60',
            'circle_type' => 'required|in:memorization,recitation,interpretation,general',
            'curriculum_focus' => 'nullable|array',
            'learning_objectives' => 'nullable|array',
            'prerequisites' => 'nullable|string|max:500',
            'enrollment_start_date' => 'required|date|after_or_equal:today',
            'enrollment_end_date' => 'required|date|after:enrollment_start_date',
            'circle_start_date' => 'required|date|after:enrollment_end_date',
            'circle_end_date' => 'nullable|date|after:circle_start_date',
            'total_sessions' => 'required|integer|min:4|max:52',
            'location_type' => 'required|in:online,physical,hybrid',
            'physical_location' => 'nullable|string|max:200',
            'online_platform' => 'nullable|string|max:100',
            'meeting_link' => 'nullable|url',
            'materials_required' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
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
            'quran_teacher_id.required' => 'معلم القرآن مطلوب',
            'quran_teacher_id.exists' => 'المعلم المحدد غير موجود',
            'name_ar.required' => 'الاسم بالعربية مطلوب',
            'name_ar.max' => 'الاسم بالعربية يجب ألا يتجاوز 100 حرف',
            'name_en.max' => 'الاسم بالإنجليزية يجب ألا يتجاوز 100 حرف',
            'description_ar.max' => 'الوصف بالعربية يجب ألا يتجاوز 500 حرف',
            'description_en.max' => 'الوصف بالإنجليزية يجب ألا يتجاوز 500 حرف',
            'level.required' => 'المستوى مطلوب',
            'level.in' => 'المستوى المحدد غير صالح',
            'target_age_group.required' => 'الفئة العمرية المستهدفة مطلوبة',
            'target_age_group.in' => 'الفئة العمرية المحددة غير صالحة',
            'min_age.required' => 'الحد الأدنى للعمر مطلوب',
            'min_age.integer' => 'الحد الأدنى للعمر يجب أن يكون رقماً صحيحاً',
            'min_age.min' => 'الحد الأدنى للعمر يجب أن يكون 5 سنوات على الأقل',
            'min_age.max' => 'الحد الأدنى للعمر يجب ألا يتجاوز 80 سنة',
            'max_age.required' => 'الحد الأقصى للعمر مطلوب',
            'max_age.integer' => 'الحد الأقصى للعمر يجب أن يكون رقماً صحيحاً',
            'max_age.min' => 'الحد الأقصى للعمر يجب أن يكون 5 سنوات على الأقل',
            'max_age.max' => 'الحد الأقصى للعمر يجب ألا يتجاوز 80 سنة',
            'max_age.gte' => 'الحد الأقصى للعمر يجب أن يكون أكبر من أو يساوي الحد الأدنى',
            'max_students.required' => 'الحد الأقصى للطلاب مطلوب',
            'max_students.integer' => 'الحد الأقصى للطلاب يجب أن يكون رقماً صحيحاً',
            'max_students.min' => 'الحد الأقصى للطلاب يجب أن يكون 3 على الأقل',
            'max_students.max' => 'الحد الأقصى للطلاب يجب ألا يتجاوز 100',
            'price_per_student.required' => 'السعر لكل طالب مطلوب',
            'price_per_student.numeric' => 'السعر لكل طالب يجب أن يكون رقماً',
            'price_per_student.min' => 'السعر لكل طالب يجب أن يكون صفراً أو أكثر',
            'price_per_student.max' => 'السعر لكل طالب يجب ألا يتجاوز 300',
            'billing_cycle.required' => 'دورة الفوترة مطلوبة',
            'billing_cycle.in' => 'دورة الفوترة المحددة غير صالحة',
            'day_of_week.required' => 'يوم الأسبوع مطلوب',
            'day_of_week.in' => 'يوم الأسبوع المحدد غير صالح',
            'start_time.required' => 'وقت البداية مطلوب',
            'start_time.date_format' => 'صيغة وقت البداية غير صالحة',
            'end_time.required' => 'وقت النهاية مطلوب',
            'end_time.date_format' => 'صيغة وقت النهاية غير صالحة',
            'end_time.after' => 'وقت النهاية يجب أن يكون بعد وقت البداية',
            'duration_minutes.required' => 'مدة الجلسة مطلوبة',
            'duration_minutes.integer' => 'مدة الجلسة يجب أن تكون رقماً صحيحاً',
            'duration_minutes.in' => 'مدة الجلسة يجب أن تكون 30 أو 60 دقيقة',
            'circle_type.required' => 'نوع الدائرة مطلوب',
            'circle_type.in' => 'نوع الدائرة المحدد غير صالح',
            'curriculum_focus.array' => 'تركيز المنهج يجب أن يكون مصفوفة',
            'learning_objectives.array' => 'أهداف التعلم يجب أن تكون مصفوفة',
            'prerequisites.max' => 'المتطلبات الأساسية يجب ألا تتجاوز 500 حرف',
            'enrollment_start_date.required' => 'تاريخ بداية التسجيل مطلوب',
            'enrollment_start_date.date' => 'تاريخ بداية التسجيل غير صالح',
            'enrollment_start_date.after_or_equal' => 'تاريخ بداية التسجيل يجب أن يكون اليوم أو بعده',
            'enrollment_end_date.required' => 'تاريخ نهاية التسجيل مطلوب',
            'enrollment_end_date.date' => 'تاريخ نهاية التسجيل غير صالح',
            'enrollment_end_date.after' => 'تاريخ نهاية التسجيل يجب أن يكون بعد تاريخ بداية التسجيل',
            'circle_start_date.required' => 'تاريخ بداية الدائرة مطلوب',
            'circle_start_date.date' => 'تاريخ بداية الدائرة غير صالح',
            'circle_start_date.after' => 'تاريخ بداية الدائرة يجب أن يكون بعد تاريخ نهاية التسجيل',
            'circle_end_date.date' => 'تاريخ نهاية الدائرة غير صالح',
            'circle_end_date.after' => 'تاريخ نهاية الدائرة يجب أن يكون بعد تاريخ بداية الدائرة',
            'total_sessions.required' => 'عدد الجلسات الكلي مطلوب',
            'total_sessions.integer' => 'عدد الجلسات الكلي يجب أن يكون رقماً صحيحاً',
            'total_sessions.min' => 'عدد الجلسات الكلي يجب أن يكون 4 على الأقل',
            'total_sessions.max' => 'عدد الجلسات الكلي يجب ألا يتجاوز 52',
            'location_type.required' => 'نوع الموقع مطلوب',
            'location_type.in' => 'نوع الموقع المحدد غير صالح',
            'physical_location.max' => 'الموقع الفعلي يجب ألا يتجاوز 200 حرف',
            'online_platform.max' => 'المنصة الإلكترونية يجب ألا تتجاوز 100 حرف',
            'meeting_link.url' => 'رابط الاجتماع غير صالح',
            'materials_required.array' => 'المواد المطلوبة يجب أن تكون مصفوفة',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
        ];
    }
}
