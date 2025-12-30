<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetParentCalendarEventsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start' => 'required|date',
            'end' => 'required|date',
        ];
    }

    /**
     * Get custom messages for validator errors (Arabic).
     *
     * @return array<string, string>
     */
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
