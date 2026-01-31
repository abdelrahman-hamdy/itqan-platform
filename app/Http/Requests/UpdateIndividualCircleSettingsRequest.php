<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndividualCircleSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'default_duration_minutes' => 'integer|min:15|max:240',
            'preferred_times' => 'array',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string|max:100',
            'recording_enabled' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }
}
