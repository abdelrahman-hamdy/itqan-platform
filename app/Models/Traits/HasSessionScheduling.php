<?php

namespace App\Models\Traits;

use App\Enums\SessionStatus;
use App\Models\User;
use App\Services\AcademyContextService;
use Carbon\Carbon;

/**
 * Trait HasSessionScheduling
 *
 * Provides scheduling functionality for session models:
 * - Date/time helpers
 * - Rescheduling logic
 * - Timing validation
 * - Join window checks
 *
 * IMPORTANT: All time comparisons use academy timezone for consistency.
 * Database stores UTC, but comparisons are done in academy local time.
 */
trait HasSessionScheduling
{
    /**
     * Get current time in academy timezone for consistent comparisons
     */
    protected function getNowInAcademyTimezone(): Carbon
    {
        return AcademyContextService::nowInAcademyTimezone();
    }

    /**
     * Scope: Get today's sessions (in academy timezone)
     */
    public function scopeToday($query)
    {
        $today = $this->getNowInAcademyTimezone()->toDateString();
        return $query->whereDate('scheduled_at', $today);
    }

    /**
     * Scope: Get upcoming sessions (using academy timezone for comparison)
     */
    public function scopeUpcoming($query)
    {
        $now = $this->getNowInAcademyTimezone();
        return $query->where('scheduled_at', '>', $now)
            ->where('status', SessionStatus::SCHEDULED);
    }

    /**
     * Scope: Get past sessions (using academy timezone for comparison)
     */
    public function scopePast($query)
    {
        $now = $this->getNowInAcademyTimezone();
        return $query->where('scheduled_at', '<', $now);
    }

    /**
     * Reschedule the session
     *
     * @param Carbon $newDateTime
     * @param string|null $reason
     * @return bool
     */
    public function reschedule(Carbon $newDateTime, ?string $reason = null): bool
    {
        if ($this->isCancelled() || $this->isCompleted()) {
            return false;
        }

        $oldDateTime = $this->scheduled_at;

        $updated = $this->update([
            'scheduled_at' => $newDateTime,
            'rescheduled_from' => $oldDateTime,
            'rescheduled_to' => $newDateTime,
            'reschedule_reason' => $reason,
        ]);

        // If meeting was already created, invalidate it
        if ($updated && $this->meeting_room_name) {
            $this->update([
                'meeting_link' => null,
                'meeting_room_name' => null,
                'meeting_id' => null,
                'meeting_expires_at' => null,
            ]);
        }

        return $updated;
    }

    /**
     * Check if session is upcoming (using academy timezone)
     */
    public function isUpcoming(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        $now = $this->getNowInAcademyTimezone();
        return $this->scheduled_at->gt($now)
            && $this->status === SessionStatus::SCHEDULED;
    }

    /**
     * Check if session is in the past (using academy timezone)
     */
    public function isPast(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        $now = $this->getNowInAcademyTimezone();
        return $this->scheduled_at->lt($now);
    }

    /**
     * Check if session is today (using academy timezone)
     */
    public function isToday(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        $now = $this->getNowInAcademyTimezone();
        return $this->scheduled_at->isSameDay($now);
    }

    /**
     * Check if session starts within the next N minutes (using academy timezone)
     *
     * @param int $minutes
     * @return bool
     */
    public function startsWithin(int $minutes): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        $now = $this->getNowInAcademyTimezone();
        return $this->scheduled_at->gt($now)
            && $now->diffInMinutes($this->scheduled_at, false) <= $minutes;
    }

    /**
     * Check if session has ended (based on scheduled time + duration, using academy timezone)
     *
     * @return bool
     */
    public function hasEnded(): bool
    {
        $now = $this->getNowInAcademyTimezone();

        if ($this->ended_at) {
            return $this->ended_at->lt($now);
        }

        if (!$this->scheduled_at || !$this->duration_minutes) {
            return false;
        }

        $endTime = $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
        return $endTime->lt($now);
    }

    /**
     * Get time until session starts (using academy timezone)
     *
     * @return int Minutes until start (negative if already started)
     */
    public function getMinutesUntilStart(): int
    {
        if (!$this->scheduled_at) {
            return 0;
        }

        $now = $this->getNowInAcademyTimezone();
        return (int) $now->diffInMinutes($this->scheduled_at, false);
    }

    /**
     * Get time since session started (using academy timezone)
     *
     * @return int Minutes since start (0 if not started)
     */
    public function getMinutesSinceStart(): int
    {
        $startTime = $this->started_at ?? $this->scheduled_at;
        $now = $this->getNowInAcademyTimezone();

        if (!$startTime || $startTime->gt($now)) {
            return 0;
        }

        return (int) $startTime->diffInMinutes($now);
    }

    /**
     * Check if user can join based on timing constraints (using academy timezone)
     */
    protected function canJoinBasedOnTiming(User $user): bool
    {
        // If no scheduled time, allow join (for manual sessions)
        if (!$this->scheduled_at) {
            return true;
        }

        // If session is marked as "ongoing" or "ready", allow all authorized users to join
        // This handles cases where sessions are kept open or status updates are delayed
        if (in_array($this->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            return true;
        }

        $now = $this->getNowInAcademyTimezone();
        $sessionStart = $this->scheduled_at;
        $sessionEnd = $sessionStart->copy()->addMinutes($this->duration_minutes ?? 60);

        // Teachers and admins can join anytime within a wider window
        if ($this->canUserManageMeeting($user)) {
            // Allow teachers to join 30 minutes before and up to 2 hours after session end
            $teacherStartWindow = $sessionStart->copy()->subMinutes(30);
            $teacherEndWindow = $sessionEnd->copy()->addHours(2);

            return $now->between($teacherStartWindow, $teacherEndWindow);
        }

        // Students can join 15 minutes before session and up to 30 minutes after session end
        $studentStartWindow = $sessionStart->copy()->subMinutes(15);
        $studentEndWindow = $sessionEnd->copy()->addMinutes(30);

        return $now->between($studentStartWindow, $studentEndWindow);
    }

    /**
     * Check if a user can join this meeting
     * Can be overridden by child classes for specific logic
     */
    public function canUserJoinMeeting(User $user): bool
    {
        // Check basic permissions first
        if (!$this->canUserManageMeeting($user) && !$this->isUserParticipant($user)) {
            return false;
        }

        // Check timing constraints
        return $this->canJoinBasedOnTiming($user);
    }

    /**
     * Check if a user can manage this meeting (create, end, control participants)
     * Must be implemented by each child class
     */
    abstract public function canUserManageMeeting(User $user): bool;

    /**
     * Check if user is a participant in this session
     * Must be implemented by each child class
     */
    abstract public function isUserParticipant(User $user): bool;

    /**
     * Check if session is cancelled
     */
    abstract public function isCancelled(): bool;

    /**
     * Check if session is completed
     */
    abstract public function isCompleted(): bool;
}
