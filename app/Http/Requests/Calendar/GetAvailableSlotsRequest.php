<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'duration' => 'integer|min:15|max:240',
            'start_time' => 'string|date_format:H:i',
            'end_time' => 'string|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'التاريخ مطلوب',
            'date.date' => 'التاريخ غير صحيح',
            'duration.integer' => 'المدة يجب أن تكون رقم',
            'duration.min' => 'المدة يجب أن تكون 15 دقيقة على الأقل',
            'duration.max' => 'المدة يجب ألا تتجاوز 240 دقيقة',
            'start_time.date_format' => 'وقت البداية يجب أن يكون بصيغة HH:MM',
            'end_time.date_format' => 'وقت النهاية يجب أن يكون بصيغة HH:MM',
        ];
    }
}
