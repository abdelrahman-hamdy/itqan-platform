<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class CheckConflictsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'exclude_type' => 'string',
            'exclude_id' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.required' => 'وقت البداية مطلوب',
            'start_time.date' => 'وقت البداية غير صحيح',
            'end_time.required' => 'وقت النهاية مطلوب',
            'end_time.date' => 'وقت النهاية غير صحيح',
            'end_time.after' => 'وقت النهاية يجب أن يكون بعد وقت البداية',
            'exclude_type.string' => 'نوع الاستبعاد يجب أن يكون نص',
            'exclude_id.integer' => 'معرف الاستبعاد يجب أن يكون رقم',
        ];
    }
}
