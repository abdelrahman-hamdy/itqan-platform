<?php

namespace App\Services;

use App\Contracts\AttendanceEventServiceInterface;
use App\Models\MeetingAttendance;
use App\Models\MeetingAttendanceEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

/**
 * Attendance Event Service
 *
 * Simple service for storing attendance events from LiveKit webhooks.
 * NO complex business logic - just data storage. Calculation happens post-meeting.
 */
class AttendanceEventService implements AttendanceEventServiceInterface
{
    /**
     * Record user joining the meeting (from webhook)
     */
    public function recordJoin($session, $user, array $eventData): bool
    {
        try {
            // Find or create MeetingAttendance record
            $attendance = MeetingAttendance::firstOrCreate(
                [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ],
                [
                    'user_type' => $this->getUserType($user),
                    'session_type' => $this->getSessionType($session),
                    'session_start_time' => $session->scheduled_at,
                    'session_end_time' => $session->scheduled_end_at ?? $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 60),
                    'session_duration_minutes' => $session->duration_minutes ?? 60,
                    'join_leave_cycles' => [],
                    'join_count' => 0,
                    'leave_count' => 0,
                    'is_calculated' => false,
                ]
            );

            // Set first_join_time if this is the first join
            if (! $attendance->first_join_time) {
                $attendance->first_join_time = $eventData['timestamp'] ?? now();
            }

            // Add join event to cycles
            $cycles = $attendance->join_leave_cycles ?? [];
            $cycles[] = [
                'type' => 'join',
                'timestamp' => $eventData['timestamp'] ?? now(),
                'event_id' => $eventData['event_id'] ?? null,
                'participant_sid' => $eventData['participant_sid'] ?? null,
            ];
            $attendance->join_leave_cycles = $cycles;
            $attendance->join_count = ($attendance->join_count ?? 0) + 1;
            $attendance->is_calculated = false;
            $attendance->save();

            // Clear cache
            $this->clearAttendanceCache($session->id, $user->id);

            // Dispatch Livewire event to update UI in real-time
            $this->dispatchAttendanceUpdate($session->id, $user->id);

            Log::info('Join event recorded', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'event_id' => $eventData['event_id'] ?? null,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to record join event', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);

            return false;
        }
    }

    /**
     * Record user leaving the meeting (from webhook)
     */
    public function recordLeave($session, $user, array $eventData): bool
    {
        try {
            // Find MeetingAttendance record
            $attendance = MeetingAttendance::where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $attendance) {
                Log::warning('No attendance record found for leave event', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ]);

                return false;
            }

            $cycles = $attendance->join_leave_cycles ?? [];
            $participantSid = $eventData['participant_sid'] ?? null;
            $leaveTime = $eventData['timestamp'] ?? now();

            // Find matching join event by participant_sid and update it
            $matchFound = false;
            if ($participantSid) {
                for ($i = count($cycles) - 1; $i >= 0; $i--) {
                    $cycle = $cycles[$i];

                    // Match by participant_sid for webhook events
                    if (isset($cycle['participant_sid']) && $cycle['participant_sid'] === $participantSid) {
                        // Check if this is a join event without a matching leave
                        if ($cycle['type'] === 'join') {
                            // Add matching leave event right after the join
                            $joinTime = is_string($cycle['timestamp']) ? \Carbon\Carbon::parse($cycle['timestamp']) : $cycle['timestamp'];
                            $leaveTimeCarbon = is_string($leaveTime) ? \Carbon\Carbon::parse($leaveTime) : $leaveTime;
                            $duration = $joinTime->diffInMinutes($leaveTimeCarbon);

                            // Insert leave event right after the join event
                            array_splice($cycles, $i + 1, 0, [[
                                'type' => 'leave',
                                'timestamp' => $leaveTime,
                                'event_id' => $eventData['event_id'] ?? null,
                                'participant_sid' => $participantSid,
                                'duration_minutes' => $duration,
                            ]]);

                            $matchFound = true;
                            Log::info('Matched leave to join event', [
                                'participant_sid' => $participantSid,
                                'duration' => $duration,
                            ]);
                            break;
                        }
                    }
                }
            }

            // If no match found, add leave event anyway (might be paired later)
            if (!$matchFound) {
                $cycles[] = [
                    'type' => 'leave',
                    'timestamp' => $leaveTime,
                    'event_id' => $eventData['event_id'] ?? null,
                    'participant_sid' => $participantSid,
                    'duration_minutes' => $eventData['duration_minutes'] ?? null,
                ];

                Log::warning('Leave event added without matching join', [
                    'participant_sid' => $participantSid,
                ]);
            }

            $attendance->join_leave_cycles = $cycles;
            $attendance->leave_count = ($attendance->leave_count ?? 0) + 1;
            $attendance->last_leave_time = $leaveTime;

            // Recalculate total duration from all complete cycles
            $attendance->total_duration_minutes = $this->calculateTotalDuration($cycles);
            $attendance->is_calculated = false;
            $attendance->save();

            // Clear cache
            $this->clearAttendanceCache($session->id, $user->id);

            // Dispatch Livewire event to update UI in real-time
            $this->dispatchAttendanceUpdate($session->id, $user->id);

            Log::info('Leave event recorded', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'event_id' => $eventData['event_id'] ?? null,
                'total_duration' => $attendance->total_duration_minutes,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to record leave event', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);

            return false;
        }
    }

    /**
     * Calculate total duration from join/leave cycles
     */
    private function calculateTotalDuration(array $cycles): int
    {
        $totalMinutes = 0;
        $lastJoinTime = null;

        foreach ($cycles as $cycle) {
            if ($cycle['type'] === 'join') {
                $lastJoinTime = $cycle['timestamp'];
            } elseif ($cycle['type'] === 'leave' && $lastJoinTime) {
                $joinTime = is_string($lastJoinTime) ? \Carbon\Carbon::parse($lastJoinTime) : $lastJoinTime;
                $leaveTime = is_string($cycle['timestamp']) ? \Carbon\Carbon::parse($cycle['timestamp']) : $cycle['timestamp'];
                $totalMinutes += $joinTime->diffInMinutes($leaveTime);
                $lastJoinTime = null; // Reset for next cycle
            }
        }

        return $totalMinutes;
    }

    /**
     * Get user type from user model
     */
    private function getUserType($user): string
    {
        if ($user->hasRole('quran_teacher') || $user->hasRole('academic_teacher')) {
            return 'teacher';
        }

        if ($user->hasRole('student')) {
            return 'student';
        }

        if ($user->hasRole('supervisor')) {
            return 'supervisor';
        }

        return 'student'; // Default
    }

    /**
     * Get session type from session model
     */
    private function getSessionType($session): string
    {
        $sessionClass = get_class($session);

        if (str_contains($sessionClass, 'QuranSession')) {
            return $session->session_type ?? 'individual'; // individual or group
        }

        if (str_contains($sessionClass, 'AcademicSession')) {
            return $session->session_type ?? 'academic';
        }

        if (str_contains($sessionClass, 'InteractiveCourseSession')) {
            return 'interactive';
        }

        return 'individual'; // Default
    }

    /**
     * Clear attendance cache for a user
     */
    private function clearAttendanceCache(int $sessionId, int $userId): void
    {
        Cache::forget("attendance_status_{$sessionId}_{$userId}");
        Cache::forget("meeting_attendance_{$sessionId}_{$userId}");
    }

    /**
     * Dispatch Livewire event to update attendance UI in real-time
     */
    private function dispatchAttendanceUpdate(int $sessionId, int $userId): void
    {
        try {
            // Broadcast attendance update event using Laravel broadcasting
            // This will be received by Livewire components listening for the event
            broadcast(new \App\Events\AttendanceUpdated($sessionId, $userId, [
                'updated_at' => now()->toIso8601String(),
            ]))->toOthers();
        } catch (\Exception $e) {
            // Silent fail - don't break attendance recording if event dispatch fails
            Log::debug('Failed to dispatch attendance update event', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $userId,
            ]);
        }
    }
}
