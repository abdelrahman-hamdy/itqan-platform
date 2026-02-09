<?php

namespace App\Models\Traits;

use App\Enums\SessionStatus;
use App\Models\User;

/**
 * Trait HasSessionStatus
 *
 * Provides session status management functionality:
 * - Status transition methods
 * - Status validation
 * - Status query scopes
 * - Status display helpers
 */
trait HasSessionStatus
{
    /**
     * Scope: Get scheduled sessions
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', SessionStatus::SCHEDULED);
    }

    /**
     * Scope: Get completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', SessionStatus::COMPLETED);
    }

    /**
     * Scope: Get cancelled sessions
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', SessionStatus::CANCELLED);
    }

    /**
     * Scope: Get ongoing sessions
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', SessionStatus::ONGOING);
    }

    /**
     * Scope: Get sessions that are active (scheduled, ready, or ongoing)
     * Replaces repeated: whereIn('status', [SCHEDULED, READY, ONGOING])
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            SessionStatus::SCHEDULED,
            SessionStatus::READY,
            SessionStatus::ONGOING,
        ]);
    }

    /**
     * Scope: Get sessions that are active or completed
     * Replaces repeated: whereIn('status', [SCHEDULED, ONGOING, COMPLETED])
     */
    public function scopeActiveOrCompleted($query)
    {
        return $query->whereIn('status', [
            SessionStatus::SCHEDULED,
            SessionStatus::READY,
            SessionStatus::ONGOING,
            SessionStatus::COMPLETED,
        ]);
    }

    /**
     * Scope: Get sessions that count towards subscription (completed or absent)
     */
    public function scopeCountable($query)
    {
        return $query->whereIn('status', [
            SessionStatus::COMPLETED,
            SessionStatus::ABSENT,
        ]);
    }

    /**
     * Scope: Get sessions in a final state (completed, cancelled, or absent)
     */
    public function scopeFinal($query)
    {
        return $query->whereIn('status', [
            SessionStatus::COMPLETED,
            SessionStatus::CANCELLED,
            SessionStatus::ABSENT,
        ]);
    }

    /**
     * Scope: Get sessions not in a final state
     */
    public function scopeNotFinal($query)
    {
        return $query->whereNotIn('status', [
            SessionStatus::COMPLETED,
            SessionStatus::CANCELLED,
            SessionStatus::ABSENT,
        ]);
    }

    /**
     * Check if session is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === SessionStatus::SCHEDULED;
    }

    /**
     * Check if session is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === SessionStatus::COMPLETED;
    }

    /**
     * Check if session is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === SessionStatus::CANCELLED;
    }

    /**
     * Check if session is ongoing
     */
    public function isOngoing(): bool
    {
        return $this->status === SessionStatus::ONGOING;
    }

    /**
     * Check if session can transition to a new status
     */
    public function canTransitionTo(SessionStatus $newStatus): bool
    {
        $currentStatus = $this->status;

        // Define valid transitions
        $validTransitions = [
            SessionStatus::SCHEDULED->value => [
                SessionStatus::READY->value,
                SessionStatus::ONGOING->value,
                SessionStatus::CANCELLED->value,
            ],
            SessionStatus::READY->value => [
                SessionStatus::ONGOING->value,
                SessionStatus::COMPLETED->value,
                SessionStatus::CANCELLED->value,
            ],
            SessionStatus::ONGOING->value => [
                SessionStatus::COMPLETED->value,
                SessionStatus::CANCELLED->value,
            ],
            SessionStatus::COMPLETED->value => [],
            SessionStatus::CANCELLED->value => [],
            SessionStatus::ABSENT->value => [],
        ];

        $currentValue = $currentStatus instanceof SessionStatus
            ? $currentStatus->value
            : $currentStatus;

        $newValue = $newStatus instanceof SessionStatus
            ? $newStatus->value
            : $newStatus;

        return in_array($newValue, $validTransitions[$currentValue] ?? []);
    }

    /**
     * Mark session as completed
     *
     * @param  array  $data  Additional data to update
     */
    public function markAsCompleted(array $data = []): bool
    {
        if (! $this->canTransitionTo(SessionStatus::COMPLETED)) {
            return false;
        }

        return $this->update(array_merge([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now(),
        ], $data));
    }

    /**
     * Mark session as cancelled
     */
    public function markAsCancelled(
        ?string $reason = null,
        ?User $cancelledBy = null,
        ?string $cancellationType = null
    ): bool {
        if (! $this->canTransitionTo(SessionStatus::CANCELLED)) {
            return false;
        }

        return $this->update([
            'status' => SessionStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
            'cancelled_at' => now(),
            'cancellation_type' => $cancellationType,
        ]);
    }

    /**
     * Mark session as ready (within join window)
     */
    public function markAsReady(): bool
    {
        if (! $this->canTransitionTo(SessionStatus::READY)) {
            return false;
        }

        return $this->update([
            'status' => SessionStatus::READY,
        ]);
    }

    /**
     * Mark session as ongoing
     */
    public function markAsOngoing(): bool
    {
        if (! $this->canTransitionTo(SessionStatus::ONGOING)) {
            return false;
        }

        return $this->update([
            'status' => SessionStatus::ONGOING,
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    /**
     * Check if session is in "preparing meeting" state
     * This happens when status is READY or ONGOING but meeting room is not yet created
     */
    public function isPreparingMeeting(): bool
    {
        $status = is_string($this->status) ? SessionStatus::from($this->status) : $this->status;

        // Meeting is being prepared if:
        // 1. Status is READY or ONGOING
        // 2. But meeting room hasn't been created yet
        return in_array($status, [SessionStatus::READY, SessionStatus::ONGOING])
            && empty($this->meeting_room_name);
    }

    /**
     * Get session status display data
     */
    public function getStatusDisplayData(): array
    {
        // Convert string status to enum if needed
        $status = is_string($this->status) ? SessionStatus::from($this->status) : $this->status;

        // Check if meeting is being prepared
        $isPreparingMeeting = $this->isPreparingMeeting();

        // Override display data when preparing meeting
        if ($isPreparingMeeting) {
            return [
                'status' => $status->value,
                'actual_status' => $status->value,
                'label' => 'جارٍ تجهيز الاجتماع...',
                'icon' => 'ri-settings-3-line',
                'color' => 'amber',
                'can_join' => false,
                'can_complete' => in_array($status, [
                    SessionStatus::READY,
                    SessionStatus::ONGOING,
                ]),
                'can_cancel' => in_array($status, [
                    SessionStatus::SCHEDULED,
                    SessionStatus::READY,
                ]),
                'can_reschedule' => in_array($status, [
                    SessionStatus::SCHEDULED,
                    SessionStatus::READY,
                ]),
                'is_upcoming' => false,
                'is_active' => true,
                'is_preparing_meeting' => true,
                'preparation_minutes' => $this->getPreparationMinutes(),
                'ending_buffer_minutes' => $this->getEndingBufferMinutes(),
                'grace_period_minutes' => $this->getGracePeriodMinutes(),
            ];
        }

        // Normal status display
        return [
            'status' => $status->value,
            'actual_status' => $status->value,
            'label' => $status->label(),
            'icon' => $status->icon(),
            'color' => $status->color(),
            'can_join' => in_array($status, [
                SessionStatus::READY,
                SessionStatus::ONGOING,
            ]),
            'can_complete' => in_array($status, [
                SessionStatus::READY,
                SessionStatus::ONGOING,
            ]),
            'can_cancel' => in_array($status, [
                SessionStatus::SCHEDULED,
                SessionStatus::READY,
            ]),
            'can_reschedule' => in_array($status, [
                SessionStatus::SCHEDULED,
                SessionStatus::READY,
            ]),
            'is_upcoming' => $status === SessionStatus::SCHEDULED && $this->scheduled_at && $this->scheduled_at->isFuture(),
            'is_active' => in_array($status, [SessionStatus::READY, SessionStatus::ONGOING]),
            'is_preparing_meeting' => false,
            'preparation_minutes' => $this->getPreparationMinutes(),
            'ending_buffer_minutes' => $this->getEndingBufferMinutes(),
            'grace_period_minutes' => $this->getGracePeriodMinutes(),
        ];
    }

    /**
     * Get preparation minutes before session
     * Can be overridden by child classes
     */
    abstract protected function getPreparationMinutes(): int;

    /**
     * Get ending buffer minutes after session
     * Can be overridden by child classes
     */
    abstract protected function getEndingBufferMinutes(): int;

    /**
     * Get grace period minutes for late joins
     * Can be overridden by child classes
     */
    abstract protected function getGracePeriodMinutes(): int;
}
