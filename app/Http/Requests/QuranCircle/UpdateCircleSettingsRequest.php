<?php

namespace App\Http\Requests\QuranCircle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCircleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'default_duration_minutes' => 'integer|min:15|max:240',
            'preferred_times' => 'array',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:50',
            'recording_enabled' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'default_duration_minutes.integer' => 'المدة الافتراضية يجب أن تكون رقم',
            'default_duration_minutes.min' => 'المدة الافتراضية يجب أن تكون 15 دقيقة على الأقل',
            'default_duration_minutes.max' => 'المدة الافتراضية يجب ألا تتجاوز 240 دقيقة',
            'preferred_times.array' => 'الأوقات المفضلة يجب أن تكون مصفوفة',
            'meeting_link.url' => 'رابط الاجتماع غير صحيح',
            'meeting_id.max' => 'معرف الاجتماع يجب ألا يتجاوز 100 حرف',
            'meeting_password.max' => 'كلمة مرور الاجتماع يجب ألا تتجاوز 50 حرف',
            'recording_enabled.boolean' => 'حالة التسجيل يجب أن تكون true أو false',
            'notes.string' => 'الملاحظات يجب أن تكون نص',
        ];
    }
}
