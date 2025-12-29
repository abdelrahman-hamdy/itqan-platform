<?php

namespace App\Http\Requests\QuranCircle;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:30|max:180',
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'العنوان يجب أن يكون نص',
            'title.max' => 'العنوان يجب ألا يتجاوز 255 حرف',
            'description.string' => 'الوصف يجب أن يكون نص',
            'description.max' => 'الوصف يجب ألا يتجاوز 1000 حرف',
            'scheduled_at.required' => 'وقت الجلسة مطلوب',
            'scheduled_at.date' => 'وقت الجلسة غير صحيح',
            'scheduled_at.after' => 'وقت الجلسة يجب أن يكون في المستقبل',
            'duration_minutes.integer' => 'المدة يجب أن تكون رقم',
            'duration_minutes.min' => 'المدة يجب أن تكون 30 دقيقة على الأقل',
            'duration_minutes.max' => 'المدة يجب ألا تتجاوز 180 دقيقة',
        ];
    }
}
