<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupCircleScheduleRequest extends FormRequest
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
}
