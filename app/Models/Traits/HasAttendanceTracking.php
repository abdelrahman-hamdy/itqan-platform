<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait HasAttendanceTracking
 *
 * Provides attendance tracking functionality for session models:
 * - Attendance record management
 * - Duration calculations
 * - Attendance status helpers
 * - Participant counting
 */
trait HasAttendanceTracking
{
    /**
     * Get meeting attendance records for this session
     */
    public function meetingAttendances(): HasMany
    {
        return $this->hasMany(\App\Models\MeetingAttendance::class, 'session_id');
    }

    /**
     * Mark attendance for a user
     *
     * @param  array  $data  Additional attendance data
     */
    public function markAttendance(User $user, array $data = []): \App\Models\MeetingAttendance
    {
        return $this->meetingAttendances()->updateOrCreate(
            ['user_id' => $user->id],
            array_merge([
                'attended_at' => now(),
                'status' => 'present',
            ], $data)
        );
    }

    /**
     * Get attendance rate for this session
     *
     * @return float Percentage of participants who attended (0-100)
     */
    public function getAttendanceRate(): float
    {
        $expectedParticipants = count($this->getParticipants());

        if ($expectedParticipants === 0) {
            return 0.0;
        }

        $attendedCount = $this->meetingAttendances()
            ->where('status', 'present')
            ->count();

        return round(($attendedCount / $expectedParticipants) * 100, 2);
    }

    /**
     * Get list of attendees
     */
    public function getAttendees(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->meetingAttendances()
            ->where('status', 'present')
            ->with('user')
            ->get()
            ->pluck('user');
    }

    /**
     * Get list of absent participants
     */
    public function getAbsentees(): array
    {
        $attendeeIds = $this->getAttendees()->pluck('id')->toArray();
        $allParticipants = $this->getParticipants();

        return array_filter($allParticipants, function ($participant) use ($attendeeIds) {
            $participantId = is_object($participant) ? $participant->id : $participant['id'];

            return ! in_array($participantId, $attendeeIds);
        });
    }

    /**
     * Check if a specific user attended
     */
    public function hasUserAttended(User $user): bool
    {
        return $this->meetingAttendances()
            ->where('user_id', $user->id)
            ->where('status', 'present')
            ->exists();
    }

    /**
     * Get total attendance duration for a user
     *
     * @return int Duration in minutes
     */
    public function getUserAttendanceDuration(User $user): int
    {
        $attendance = $this->meetingAttendances()
            ->where('user_id', $user->id)
            ->first();

        if (! $attendance) {
            return 0;
        }

        if ($attendance->attended_at && $attendance->left_at) {
            return $attendance->attended_at->diffInMinutes($attendance->left_at);
        }

        if ($attendance->duration_minutes) {
            return $attendance->duration_minutes;
        }

        return 0;
    }

    /**
     * Get average attendance duration across all participants
     *
     * @return float Duration in minutes
     */
    public function getAverageAttendanceDuration(): float
    {
        $attendances = $this->meetingAttendances()
            ->where('status', 'present')
            ->get();

        if ($attendances->isEmpty()) {
            return 0.0;
        }

        $totalDuration = $attendances->sum(function ($attendance) {
            return $attendance->duration_minutes ?? 0;
        });

        return round($totalDuration / $attendances->count(), 2);
    }

    /**
     * Update participants count
     */
    public function updateParticipantsCount(): int
    {
        $count = $this->meetingAttendances()
            ->where('status', 'present')
            ->count();

        $this->update(['participants_count' => $count]);

        return $count;
    }

    /**
     * Check if session has any attendees
     */
    public function hasAttendees(): bool
    {
        return $this->meetingAttendances()
            ->where('status', 'present')
            ->exists();
    }

    /**
     * Get all participants for this session
     * Must be implemented by each child class
     */
    abstract public function getParticipants(): array;
}
