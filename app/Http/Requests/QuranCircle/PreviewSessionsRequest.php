<?php

namespace App\Http\Requests\QuranCircle;

use Illuminate\Foundation\Http\FormRequest;

class PreviewSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'weekly_schedule' => 'required|array|min:1',
            'weekly_schedule.*.day' => 'required|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'weekly_schedule.*.time' => 'required|regex:/^\d{2}:\d{2}$/',
            'schedule_starts_at' => 'required|date',
            'schedule_ends_at' => 'nullable|date|after:schedule_starts_at',
            'preview_days' => 'integer|min:7|max:90',
        ];
    }

    public function messages(): array
    {
        return [
            'weekly_schedule.required' => 'الجدول الأسبوعي مطلوب',
            'weekly_schedule.array' => 'الجدول الأسبوعي يجب أن يكون مصفوفة',
            'weekly_schedule.min' => 'يجب إضافة يوم واحد على الأقل',
            'weekly_schedule.*.day.required' => 'اليوم مطلوب',
            'weekly_schedule.*.day.in' => 'اليوم غير صحيح',
            'weekly_schedule.*.time.required' => 'الوقت مطلوب',
            'weekly_schedule.*.time.regex' => 'الوقت يجب أن يكون بصيغة HH:MM',
            'schedule_starts_at.required' => 'تاريخ البدء مطلوب',
            'schedule_starts_at.date' => 'تاريخ البدء غير صحيح',
            'schedule_ends_at.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'preview_days.min' => 'عدد أيام المعاينة يجب أن يكون 7 أيام على الأقل',
            'preview_days.max' => 'عدد أيام المعاينة يجب ألا يتجاوز 90 يوم',
        ];
    }
}
