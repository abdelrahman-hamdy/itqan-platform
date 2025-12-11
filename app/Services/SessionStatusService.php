<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Events\SessionCompletedEvent;
use App\Models\AcademySettings;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SessionStatusService
{
    // ARCHITECTURAL FIX: Circular dependency broken using Laravel Events
    // SessionCompletedEvent dispatched when session completes -> FinalizeAttendanceListener handles attendance

    /**
     * Transition session from SCHEDULED to READY
     * Called when preparation time begins (default: 15 minutes before session)
     */
    public function transitionToReady(QuranSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            Log::warning('Cannot transition to READY: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::READY,
            'preparation_completed_at' => now(),
        ]);

        // Create meeting room when session becomes ready
        $this->createMeetingForSession($session);

        // Send notifications to participants
        $this->sendSessionReadyNotifications($session);

        Log::info('Session transitioned to READY', [
            'session_id' => $session->id,
            'scheduled_at' => $session->scheduled_at,
        ]);

        return true;
    }

    /**
     * Create meeting room for session when it becomes ready
     */
    private function createMeetingForSession(QuranSession $session): void
    {
        try {
            // Only create if meeting doesn't already exist
            if ($session->meeting_room_name) {
                Log::info('Meeting already exists for session', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);

                return;
            }

            // Use the session's generateMeetingLink method which creates the meeting
            $meetingConfig = $session->getMeetingConfiguration();
            $meetingUrl = $session->generateMeetingLink([
                'max_participants' => $meetingConfig['max_participants'],
                'recording_enabled' => $meetingConfig['recording_enabled'],
            ]);

            Log::info('Meeting created for ready session', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
                'meeting_url' => $meetingUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create meeting for ready session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Transition session from READY to ONGOING
     * Called when first participant joins the meeting
     */
    public function transitionToOngoing(QuranSession $session): bool
    {
        if ($session->status !== SessionStatus::READY) {
            Log::warning('Cannot transition to ONGOING: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        // NEW: Validate session time has arrived (with 15-minute early grace period)
        $allowEarlyJoinMinutes = 15; // Allow joining 15 minutes early
        $earliestJoinTime = $session->scheduled_at->copy()->subMinutes($allowEarlyJoinMinutes);

        if (now()->lt($earliestJoinTime)) {
            Log::warning('Cannot transition to ONGOING: session time has not arrived', [
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at,
                'current_time' => now(),
                'earliest_join_time' => $earliestJoinTime,
            ]);

            return false;
        }

        // NEW: Safety check - don't allow transition for sessions too far in future
        $maxFutureHours = 2; // Don't allow ongoing status for sessions more than 2 hours in future
        if ($session->scheduled_at->gt(now()->addHours($maxFutureHours))) {
            Log::warning('Cannot transition to ONGOING: session too far in future', [
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at,
                'current_time' => now(),
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        // Send notifications when session starts
        $this->sendSessionStartedNotifications($session);

        Log::info('Session transitioned to ONGOING', [
            'session_id' => $session->id,
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Transition session from ONGOING to COMPLETED
     * Called when session naturally ends or teacher marks it complete
     * CRITICAL FIX: Also closes the LiveKit meeting room to prevent new joins
     * CRITICAL FIX: Finalizes attendance and syncs to student reports
     */
    public function transitionToCompleted(QuranSession $session): bool
    {
        if (! in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            Log::warning('Cannot transition to COMPLETED: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now(),
            'actual_duration_minutes' => $this->calculateActualDuration($session),
        ]);

        // ARCHITECTURAL FIX: Dispatch event to finalize attendance (decoupled from MeetingAttendanceService)
        // FinalizeAttendanceListener handles the attendance calculation and report syncing
        SessionCompletedEvent::dispatch($session, 'quran');

        Log::info('SessionCompletedEvent dispatched for attendance finalization', [
            'session_id' => $session->id,
            'session_type' => 'quran',
        ]);

        // CRITICAL FIX: Close the LiveKit meeting room to prevent new joins
        if ($session->meeting_room_name) {
            try {
                $session->endMeeting();

                Log::info('Meeting room closed on session completion', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to close meeting room on completion', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Handle subscription counting for individual sessions
        if ($session->session_type === 'individual') {
            $this->handleIndividualSessionCompletion($session);
        }

        // Send notifications when session completes
        $this->sendSessionCompletedNotifications($session);

        Log::info('Session transitioned to COMPLETED', [
            'session_id' => $session->id,
            'ended_at' => now(),
            'actual_duration' => $session->actual_duration_minutes,
        ]);

        return true;
    }

    /**
     * Transition session to CANCELLED
     * Called when teacher/admin cancels the session
     */
    public function transitionToCancelled(QuranSession $session, ?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (! in_array($session->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            Log::warning('Cannot transition to CANCELLED: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        // Cancelled sessions don't count towards subscription
        Log::info('Session transitioned to CANCELLED', [
            'session_id' => $session->id,
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        return true;
    }

    /**
     * Transition session to ABSENT (individual sessions only)
     * Called when student doesn't join within grace period
     */
    public function transitionToAbsent(QuranSession $session): bool
    {
        if ($session->session_type !== 'individual') {
            Log::warning('Cannot transition to ABSENT: not an individual session', [
                'session_id' => $session->id,
                'session_type' => $session->session_type,
            ]);

            return false;
        }

        if (! in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            Log::warning('Cannot transition to ABSENT: invalid current status', [
                'session_id' => $session->id,
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::ABSENT,
            'ended_at' => now(),
            'attendance_status' => 'absent',
        ]);

        // Mark as absent in meeting attendance
        $this->recordAbsentStatus($session);

        // Absent sessions count towards subscription (this is by design)
        $session->updateSubscriptionUsage();

        Log::info('Session transitioned to ABSENT', [
            'session_id' => $session->id,
            'student_id' => $session->student_id,
        ]);

        return true;
    }

    /**
     * Check if session should transition to READY
     */
    public function shouldTransitionToReady(QuranSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        // Get preparation minutes from academy settings
        $academySettings = AcademySettings::where('academy_id', $session->academy_id)->first();
        $preparationMinutes = $academySettings?->default_preparation_minutes ?? 10;
        $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);

        // NEW: Add safety check - don't process sessions too far in future
        $maxFutureHours = 24; // Don't process sessions more than 24h in future
        $maxFutureTime = now()->addHours($maxFutureHours);

        if ($session->scheduled_at->gt($maxFutureTime)) {
            return false; // Skip sessions too far in future
        }

        // NEW: Also don't process sessions older than 24 hours
        $minPastTime = now()->subHours(24);
        if ($session->scheduled_at->lt($minPastTime)) {
            return false; // Skip very old sessions
        }

        return now()->gte($preparationTime);
    }

    /**
     * Check if session should transition to ABSENT (individual only)
     */
    public function shouldTransitionToAbsent(QuranSession $session): bool
    {
        if ($session->session_type !== 'individual' || $session->status !== SessionStatus::READY) {
            return false;
        }

        // Get late tolerance from academy settings
        $academySettings = AcademySettings::where('academy_id', $session->academy_id)->first();
        $graceMinutes = $academySettings?->default_late_tolerance_minutes ?? 15;
        $graceDeadline = $session->scheduled_at->copy()->addMinutes($graceMinutes);

        // Check if no one has joined within grace period
        $hasParticipants = $session->meetingAttendances()
            ->where('total_duration_minutes', '>', 0)
            ->exists();

        return now()->gt($graceDeadline) && ! $hasParticipants;
    }

    /**
     * Check if session should auto-complete
     */
    public function shouldAutoComplete(QuranSession $session): bool
    {
        if (! in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            return false;
        }

        // Get buffer minutes from academy settings
        $academySettings = AcademySettings::where('academy_id', $session->academy_id)->first();
        $endingBufferMinutes = $academySettings?->default_buffer_minutes ?? 5;
        $autoCompleteTime = $session->scheduled_at
            ->copy()
            ->addMinutes($session->duration_minutes)
            ->addMinutes($endingBufferMinutes);

        return now()->gte($autoCompleteTime);
    }

    /**
     * Process status transitions for a collection of sessions
     */
    public function processStatusTransitions(Collection $sessions): array
    {
        $results = [
            'transitions_to_ready' => 0,
            'transitions_to_absent' => 0,
            'transitions_to_completed' => 0,
            'errors' => [],
        ];

        foreach ($sessions as $session) {
            try {
                // Check for READY transition
                if ($this->shouldTransitionToReady($session)) {
                    if ($this->transitionToReady($session)) {
                        $results['transitions_to_ready']++;
                    }
                }

                // Check for ABSENT transition (individual sessions only)
                if ($this->shouldTransitionToAbsent($session)) {
                    if ($this->transitionToAbsent($session)) {
                        $results['transitions_to_absent']++;
                    }
                }

                // Check for auto-completion
                if ($this->shouldAutoComplete($session)) {
                    if ($this->transitionToCompleted($session)) {
                        $results['transitions_to_completed']++;
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Error processing session status transition', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get the appropriate circle (individual or group) for a session
     */
    private function getCircleForSession(QuranSession $session)
    {
        return $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
    }

    /**
     * Calculate actual session duration
     */
    private function calculateActualDuration(QuranSession $session): int
    {
        if (! $session->started_at) {
            return 0;
        }

        $endTime = $session->ended_at ?? now();

        return $session->started_at->diffInMinutes($endTime);
    }

    /**
     * Handle individual session completion logic
     * CRITICAL FIX: Use StudentSessionReport as primary source, not MeetingAttendance
     */
    private function handleIndividualSessionCompletion(QuranSession $session): void
    {
        // CRITICAL FIX: Check StudentSessionReport first (most reliable source)
        $studentReport = \App\Models\StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $session->student_id)
            ->first();

        if ($studentReport) {
            // Use StudentSessionReport data (teacher-verified, comprehensive)
            if ($studentReport->attendance_status === 'absent') {
                $session->update(['status' => SessionStatus::ABSENT]);
                Log::info('Individual session marked as ABSENT based on StudentSessionReport', [
                    'session_id' => $session->id,
                    'student_id' => $session->student_id,
                    'report_status' => $studentReport->attendance_status,
                    'report_minutes' => $studentReport->actual_attendance_minutes,
                ]);
            } else {
                Log::info('Individual session kept as COMPLETED based on StudentSessionReport', [
                    'session_id' => $session->id,
                    'student_id' => $session->student_id,
                    'report_status' => $studentReport->attendance_status,
                    'report_minutes' => $studentReport->actual_attendance_minutes,
                ]);
            }

            return;
        }

        // Fallback: Check MeetingAttendance only if no StudentSessionReport exists
        $studentAttendance = $session->meetingAttendances()
            ->where('user_id', $session->student_id)
            ->where('user_type', 'student')
            ->first();

        if ($studentAttendance && $studentAttendance->attendance_status === 'absent') {
            $session->update(['status' => SessionStatus::ABSENT]);
            Log::info('Individual session marked as ABSENT based on MeetingAttendance (fallback)', [
                'session_id' => $session->id,
                'student_id' => $session->student_id,
                'no_student_report' => true,
            ]);
        }
    }

    /**
     * Record absent status in meeting attendance
     */
    private function recordAbsentStatus(QuranSession $session): void
    {
        if ($session->session_type === 'individual' && $session->student_id) {
            $attendance = $session->meetingAttendances()
                ->where('user_id', $session->student_id)
                ->first();

            if (! $attendance) {
                $student = $session->student;
                if ($student) {
                    $attendance = MeetingAttendance::create([
                        'session_id' => $session->id,
                        'user_id' => $session->student_id,
                        'user_type' => 'student',
                        'session_type' => 'individual',
                        'attendance_status' => 'absent',
                        'attendance_percentage' => 0,
                        'total_duration_minutes' => 0,
                        'is_calculated' => true,
                        'attendance_calculated_at' => now(),
                    ]);

                    // Send absent notification to student
                    try {
                        $notificationService = app(\App\Services\NotificationService::class);
                        $notificationService->sendAttendanceMarkedNotification(
                            $attendance,
                            $student,
                            'absent'
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send absent notification', [
                            'session_id' => $session->id,
                            'student_id' => $session->student_id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Also notify parents about absence
                    try {
                        $parentNotificationService = app(\App\Services\ParentNotificationService::class);
                        $parents = $parentNotificationService->getParentsForStudent($student);
                        foreach ($parents as $parent) {
                            $notificationService->send(
                                $parent->user,
                                \App\Enums\NotificationType::ATTENDANCE_MARKED_ABSENT,
                                [
                                    'child_name' => $student->name,
                                    'session_title' => $session->title ?? 'جلسة قرآنية',
                                    'date' => $session->scheduled_at->format('Y-m-d'),
                                ],
                                route('parent.sessions.show', ['sessionType' => 'quran', 'session' => $session->id]),
                                [
                                    'child_id' => $student->id,
                                    'session_id' => $session->id,
                                ],
                                true // important
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to send parent absent notification', [
                            'session_id' => $session->id,
                            'student_id' => $session->student_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } else {
                $attendance->update([
                    'attendance_status' => 'absent',
                    'is_calculated' => true,
                    'attendance_calculated_at' => now(),
                ]);

                // Send absent notification to student
                try {
                    $student = $session->student;
                    if ($student) {
                        $notificationService = app(\App\Services\NotificationService::class);
                        $notificationService->sendAttendanceMarkedNotification(
                            $attendance,
                            $student,
                            'absent'
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send absent notification', [
                        'session_id' => $session->id,
                        'student_id' => $session->student_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Also notify parents about absence
                try {
                    $student = $session->student;
                    if ($student) {
                        $notificationService = app(\App\Services\NotificationService::class);
                        $parentNotificationService = app(\App\Services\ParentNotificationService::class);
                        $parents = $parentNotificationService->getParentsForStudent($student);
                        foreach ($parents as $parent) {
                            $notificationService->send(
                                $parent->user,
                                \App\Enums\NotificationType::ATTENDANCE_MARKED_ABSENT,
                                [
                                    'child_name' => $student->name,
                                    'session_title' => $session->title ?? 'جلسة قرآنية',
                                    'date' => $session->scheduled_at->format('Y-m-d'),
                                ],
                                route('parent.sessions.show', ['sessionType' => 'quran', 'session' => $session->id]),
                                [
                                    'child_id' => $student->id,
                                    'session_id' => $session->id,
                                ],
                                true // important
                            );
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send parent absent notification', [
                        'session_id' => $session->id,
                        'student_id' => $session->student_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Send notifications when session becomes ready
     */
    private function sendSessionReadyNotifications(QuranSession $session): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $parentNotificationService = app(\App\Services\ParentNotificationService::class);

            // Send reminder notification to students (30 min before)
            if ($session->session_type === 'individual' && $session->student) {
                $notificationService->sendSessionReminderNotification($session, $session->student);
                // Also notify parents
                $parentNotificationService->sendSessionReminder($session);
            } elseif ($session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $notificationService->sendSessionReminderNotification($session, $student->user);
                    }
                }
                // Notify all parents of group session students
                foreach ($session->circle->students as $student) {
                    // Create temporary session-like object for group students
                    $tempSession = clone $session;
                    $tempSession->student_id = $student->user_id;
                    $parentNotificationService->sendSessionReminder($tempSession);
                }
            }

            // Notify teacher that meeting room is ready
            if ($session->teacher) {
                $notificationService->send(
                    $session->teacher,
                    \App\Enums\NotificationType::MEETING_ROOM_READY,
                    [
                        'session_title' => $session->title ?? 'جلسة قرآنية',
                    ],
                    '/teacher/session-detail/' . $session->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session ready notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when session starts
     */
    private function sendSessionStartedNotifications(QuranSession $session): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);

            // Notify students that session has started
            if ($session->session_type === 'individual' && $session->student) {
                $notificationService->send(
                    $session->student,
                    \App\Enums\NotificationType::SESSION_STARTED,
                    [
                        'session_title' => $session->title ?? 'جلسة قرآنية',
                    ],
                    '/student/session-detail/' . $session->id
                );
            } elseif ($session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $notificationService->send(
                            $student->user,
                            \App\Enums\NotificationType::SESSION_STARTED,
                            [
                                'session_title' => $session->title ?? 'جلسة قرآنية',
                            ],
                            '/student/session-detail/' . $session->id
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session started notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when session completes
     */
    private function sendSessionCompletedNotifications(QuranSession $session): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);

            // Notify students that session has completed
            if ($session->session_type === 'individual' && $session->student) {
                $notificationService->send(
                    $session->student,
                    \App\Enums\NotificationType::SESSION_COMPLETED,
                    [
                        'session_title' => $session->title ?? 'جلسة قرآنية',
                    ],
                    '/student/session-detail/' . $session->id
                );
            } elseif ($session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $notificationService->send(
                            $student->user,
                            \App\Enums\NotificationType::SESSION_COMPLETED,
                            [
                                'session_title' => $session->title ?? 'جلسة قرآنية',
                            ],
                            '/student/session-detail/' . $session->id
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session completed notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
