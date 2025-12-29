<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class GetStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'month' => 'date_format:Y-m',
        ];
    }

    public function messages(): array
    {
        return [
            'month.date_format' => 'صيغة الشهر يجب أن تكون YYYY-MM',
        ];
    }
}
