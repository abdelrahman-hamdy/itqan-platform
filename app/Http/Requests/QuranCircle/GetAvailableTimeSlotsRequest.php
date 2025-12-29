<?php

namespace App\Http\Requests\QuranCircle;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'integer|min:15|max:240',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'التاريخ مطلوب',
            'date.date' => 'التاريخ غير صحيح',
            'date.after_or_equal' => 'التاريخ يجب أن يكون اليوم أو بعده',
            'duration.integer' => 'المدة يجب أن تكون رقم',
            'duration.min' => 'المدة يجب أن تكون 15 دقيقة على الأقل',
            'duration.max' => 'المدة يجب ألا تتجاوز 240 دقيقة',
        ];
    }
}
