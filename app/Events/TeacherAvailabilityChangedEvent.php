<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a teacher's availability changes.
 *
 * Use cases:
 * - Update calendar availability slots
 * - Notify students of schedule changes
 * - Reschedule affected sessions
 * - Update booking system availability
 */
class TeacherAvailabilityChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $teacher,
        public readonly string $teacherType,
        public readonly array $changedDays = []
    ) {}

    /**
     * Get the teacher user model.
     */
    public function getTeacher(): User
    {
        return $this->teacher;
    }

    /**
     * Get the teacher type (quran, academic).
     */
    public function getTeacherType(): string
    {
        return $this->teacherType;
    }

    /**
     * Get the days that were changed.
     */
    public function getChangedDays(): array
    {
        return $this->changedDays;
    }

    /**
     * Check if availability is now restricted.
     */
    public function hasChangedDays(): bool
    {
        return ! empty($this->changedDays);
    }
}
