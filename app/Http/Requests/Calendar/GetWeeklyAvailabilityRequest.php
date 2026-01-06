<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class GetWeeklyAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user && ($user->isQuranTeacher() || $user->isAcademicTeacher());
    }

    public function rules(): array
    {
        return [
            'week_start' => 'date',
        ];
    }

    public function messages(): array
    {
        return [
            'week_start.date' => 'تاريخ بداية الأسبوع غير صحيح',
        ];
    }
}
