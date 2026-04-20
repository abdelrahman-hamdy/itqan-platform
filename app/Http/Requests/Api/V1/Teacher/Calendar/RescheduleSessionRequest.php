<?php

namespace App\Http\Requests\Api\V1\Teacher\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('effective_teacher');
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['nullable', 'integer'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
