<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Enums\EducationalQualification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTeacherProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only the authenticated teacher may update their own profile.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        // Must be a teacher type
        if (! $user->isQuranTeacher() && ! $user->isAcademicTeacher()) {
            return false;
        }

        // Must be updating their own profile (route {id} must match authenticated user's profile)
        $profileId = $this->route('id') ?? $this->route('teacher');
        if ($profileId !== null && (string) $profileId !== (string) $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'preview_video' => 'nullable|file|mimes:mp4,webm,mov|max:51200',
            'remove_preview_video' => 'nullable|boolean',
            'bio_arabic' => 'nullable|string|max:1000',
            'bio_english' => 'nullable|string|max:1000',
            'available_days' => 'nullable|array',
            'available_time_start' => 'nullable|date_format:H:i',
            'available_time_end' => 'nullable|date_format:H:i',
            'teaching_experience_years' => 'nullable|integer|min:0|max:50',
            'certifications' => 'nullable|array',
            'languages' => 'nullable|array',
        ];

        // Add Quran teacher specific fields
        if ($this->user()->isQuranTeacher()) {
            $rules['educational_qualification'] = 'nullable|string|in:bachelor,master,phd,diploma,other';
        }

        // Add Academic teacher specific fields
        if ($this->user()->isAcademicTeacher()) {
            $rules['education_level'] = ['nullable', new Enum(EducationalQualification::class)];
            $rules['university'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'الاسم الأول مطلوب',
            'first_name.string' => 'يجب أن يكون الاسم الأول نصاً',
            'first_name.max' => 'يجب ألا يتجاوز الاسم الأول 255 حرفاً',
            'last_name.required' => 'اسم العائلة مطلوب',
            'last_name.string' => 'يجب أن يكون اسم العائلة نصاً',
            'last_name.max' => 'يجب ألا يتجاوز اسم العائلة 255 حرفاً',
            'phone.string' => 'يجب أن يكون رقم الهاتف نصاً',
            'phone.max' => 'يجب ألا يتجاوز رقم الهاتف 20 حرفاً',
            'avatar.image' => 'يجب أن تكون الصورة ملف صورة',
            'avatar.mimes' => 'يجب أن تكون الصورة من نوع: jpeg, png, jpg, gif',
            'avatar.max' => 'يجب ألا يتجاوز حجم الصورة 2 ميجابايت',
            'preview_video.file' => 'يجب أن يكون الفيديو التعريفي ملفاً',
            'preview_video.mimes' => 'يجب أن يكون الفيديو من نوع: mp4, webm, mov',
            'preview_video.max' => 'يجب ألا يتجاوز حجم الفيديو 50 ميجابايت',
            'bio_arabic.string' => 'يجب أن تكون السيرة الذاتية بالعربية نصاً',
            'bio_arabic.max' => 'يجب ألا تتجاوز السيرة الذاتية بالعربية 1000 حرف',
            'bio_english.string' => 'يجب أن تكون السيرة الذاتية بالإنجليزية نصاً',
            'bio_english.max' => 'يجب ألا تتجاوز السيرة الذاتية بالإنجليزية 1000 حرف',
            'available_days.array' => 'يجب أن تكون الأيام المتاحة مصفوفة',
            'available_time_start.date_format' => 'يجب أن يكون وقت البداية بصيغة ساعة:دقيقة',
            'available_time_end.date_format' => 'يجب أن يكون وقت النهاية بصيغة ساعة:دقيقة',
            'teaching_experience_years.integer' => 'يجب أن تكون سنوات الخبرة رقماً صحيحاً',
            'teaching_experience_years.min' => 'يجب ألا تقل سنوات الخبرة عن 0',
            'teaching_experience_years.max' => 'يجب ألا تتجاوز سنوات الخبرة 50 سنة',
            'certifications.array' => 'يجب أن تكون الشهادات مصفوفة',
            'languages.array' => 'يجب أن تكون اللغات مصفوفة',
            'educational_qualification.string' => 'يجب أن يكون المؤهل التعليمي نصاً',
            'educational_qualification.in' => 'المؤهل التعليمي غير صحيح',
            'university.string' => 'يجب أن يكون اسم الجامعة نصاً',
            'university.max' => 'يجب ألا يتجاوز اسم الجامعة 255 حرفاً',
        ];
    }
}
