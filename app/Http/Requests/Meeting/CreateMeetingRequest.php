<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class CreateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:quran_sessions,id',
            'max_participants' => 'nullable|integer|min:2|max:100',
            'recording_enabled' => 'nullable|boolean',
            'max_duration' => 'nullable|integer|min:15|max:480',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Use sessionId from route if provided, otherwise from request body
        $sessionId = $this->route('sessionId') ?? $this->input('session_id');

        if ($sessionId) {
            $this->merge(['session_id' => $sessionId]);
        }
    }
}
