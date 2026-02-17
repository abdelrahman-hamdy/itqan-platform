<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PreviewGroupCircleSessionsRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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
}
