<?php

namespace App\Http\Requests\Api\V1\Teacher\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class CheckConflictsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('effective_teacher');
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['nullable', 'integer'],
            'date' => ['required', 'date'],
            'time' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):(00|15|30|45)$/'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'exclude_type' => ['nullable', 'string', 'in:quran_session,academic_session,course_session,circle_session'],
            'exclude_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'time.regex' => __('calendar.quarter_hour_required'),
        ];
    }
}
