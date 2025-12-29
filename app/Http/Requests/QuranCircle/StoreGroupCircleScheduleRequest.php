<?php

namespace App\Http\Requests\QuranCircle;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupCircleScheduleRequest extends FormRequest
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
            'weekly_schedule.*.duration' => 'integer|min:15|max:240',
            'schedule_starts_at' => 'required|date|after_or_equal:today',
            'schedule_ends_at' => 'nullable|date|after:schedule_starts_at',
            'session_title_template' => 'nullable|string|max:255',
            'session_description_template' => 'nullable|string',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:100',
            'meeting_password' => 'nullable|string|max:50',
            'recording_enabled' => 'boolean',
            'generate_ahead_days' => 'integer|min:7|max:90',
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
            'weekly_schedule.*.duration.min' => 'المدة يجب أن تكون 15 دقيقة على الأقل',
            'weekly_schedule.*.duration.max' => 'المدة يجب ألا تتجاوز 240 دقيقة',
            'schedule_starts_at.required' => 'تاريخ البدء مطلوب',
            'schedule_starts_at.after_or_equal' => 'تاريخ البدء يجب أن يكون اليوم أو بعده',
            'schedule_ends_at.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'meeting_link.url' => 'رابط الاجتماع غير صحيح',
            'generate_ahead_days.min' => 'عدد الأيام يجب أن يكون 7 أيام على الأقل',
            'generate_ahead_days.max' => 'عدد الأيام يجب ألا يتجاوز 90 يوم',
        ];
    }
}
