<?php

namespace App\Http\Requests\Supervisor;

use App\Enums\SessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isSupervisor() || $user->isSuperAdmin() || $user->isAcademyAdmin());
    }

    public function rules(): array
    {
        $statusValues = collect(SessionStatus::cases())->map(fn ($s) => $s->value)->toArray();

        return [
            'status' => ['nullable', Rule::in($statusValues)],
            'scheduled_at' => ['nullable', 'date'],
            'supervisor_notes' => ['nullable', 'string', 'max:2000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => __('validation.in', ['attribute' => __('supervisor.sessions.edit_status')]),
            'scheduled_at.date' => __('validation.date', ['attribute' => __('supervisor.sessions.edit_scheduled_at')]),
            'supervisor_notes.max' => __('validation.max.string', ['attribute' => __('supervisor.sessions.edit_notes'), 'max' => 2000]),
            'cancellation_reason.max' => __('validation.max.string', ['attribute' => __('supervisor.sessions.cancel_reason_label'), 'max' => 500]),
        ];
    }
}
