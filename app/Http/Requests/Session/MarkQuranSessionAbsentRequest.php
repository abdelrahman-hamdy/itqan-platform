<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class MarkQuranSessionAbsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.string' => 'السبب يجب أن يكون نص',
            'reason.max' => 'السبب يجب ألا يتجاوز 500 حرف',
        ];
    }
}
