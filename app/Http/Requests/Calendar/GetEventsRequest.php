<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class GetEventsRequest extends FormRequest
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
            'types' => 'array',
            'status' => 'array',
            'search' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'start.required' => 'تاريخ البداية مطلوب',
            'start.date' => 'تاريخ البداية غير صحيح',
            'end.required' => 'تاريخ النهاية مطلوب',
            'end.date' => 'تاريخ النهاية غير صحيح',
            'types.array' => 'الأنواع يجب أن تكون مصفوفة',
            'status.array' => 'الحالات يجب أن تكون مصفوفة',
            'search.string' => 'البحث يجب أن يكون نص',
            'search.max' => 'البحث يجب ألا يتجاوز 255 حرف',
        ];
    }
}
