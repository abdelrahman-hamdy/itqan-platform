<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
            'format' => 'in:ics,csv',
        ];
    }

    public function messages(): array
    {
        return [
            'start.required' => 'تاريخ البداية مطلوب',
            'start.date' => 'تاريخ البداية غير صحيح',
            'end.required' => 'تاريخ النهاية مطلوب',
            'end.date' => 'تاريخ النهاية غير صحيح',
            'end.after_or_equal' => 'تاريخ النهاية يجب أن يكون مساوياً أو بعد تاريخ البداية',
            'format.in' => 'الصيغة يجب أن تكون ics أو csv',
        ];
    }
}
