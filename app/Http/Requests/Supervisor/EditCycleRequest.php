<?php

declare(strict_types=1);

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * F2 cycle editor — admin-only mutation of a single SubscriptionCycle row.
 *
 * Only fields listed in `rules()` survive validation. Forbidden fields
 * (sessions_used, sessions_completed, sessions_missed, sessions_remaining,
 * cycle_state, package_id, pricing_source, payment_status, override_*) are
 * intentionally absent so they're stripped from `validated()` even if a
 * stray POST tries to set them.
 */
class EditCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role matrix is enforced by `BaseSupervisorWebController::canManageSubscriptions()`
        // at the top of `editCycle()`. This request only needs the FormRequest to
        // strip forbidden fields via `rules()` + `validated()`.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Coordinated-field edits (block-on-conflict in CycleEditValidator).
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'total_sessions' => ['sometimes', 'integer', 'min:0', 'max:1000'],

            // Free-to-edit safe fields (no contract cascade).
            'grace_period_ends_at' => ['sometimes', 'nullable', 'date'],
            'archived_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
