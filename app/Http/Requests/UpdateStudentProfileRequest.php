<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Helpers\CountryList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isStudent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'nationality' => 'nullable|string|in:'.CountryList::validationRule(),
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:20',
            'grade_level_id' => ['nullable', Rule::exists('academic_grade_levels', 'id')->where('academy_id', $this->user()->academy_id)],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'first_name.required' => 'الاسم الأول مطلوب',
            'first_name.max' => 'الاسم الأول يجب ألا يتجاوز 255 حرفاً',
            'last_name.required' => 'اسم العائلة مطلوب',
            'last_name.max' => 'اسم العائلة يجب ألا يتجاوز 255 حرفاً',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 رقماً',
            'birth_date.date' => 'تاريخ الميلاد غير صالح',
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى',
            'nationality.in' => 'الجنسية غير صالحة',
            'address.max' => 'العنوان يجب ألا يتجاوز 500 حرفاً',
            'emergency_contact.max' => 'رقم الطوارئ يجب ألا يتجاوز 20 رقماً',
            'grade_level_id.exists' => 'المستوى الدراسي غير موجود',
            'avatar.image' => 'الملف يجب أن يكون صورة',
            'avatar.mimes' => 'الصورة يجب أن تكون من نوع jpeg, png, jpg, أو gif',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }
}
