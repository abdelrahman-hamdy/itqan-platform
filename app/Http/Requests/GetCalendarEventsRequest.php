<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCalendarEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'start' => 'required|date',
            'end' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'start.required' => 'تاريخ البداية مطلوب',
            'start.date' => 'تاريخ البداية غير صحيح',
            'end.required' => 'تاريخ النهاية مطلوب',
            'end.date' => 'تاريخ النهاية غير صحيح',
        ];
    }
}
