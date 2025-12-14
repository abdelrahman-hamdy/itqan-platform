<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Events\SessionCompletedEvent;
use App\Models\AcademicSession;
use App\Models\AcademySettings;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Unified Session Status Service
 *
 * Handles status transitions for ALL session types:
 * - QuranSession (individual and group circles)
 * - AcademicSession (individual lessons)
 * - InteractiveCourseSession (group courses)
 *
 * All timing values are read from AcademySettings - NO hardcoded values.
 */
class UnifiedSessionStatusService
{
    /**
     * Get academy settings for a session
     */
    protected function getAcademySettings(BaseSession $session): ?AcademySettings
    {
        $academyId = $session->academy_id;
        if (!$academyId) {
            return null;
        }

        return AcademySettings::where('academy_id', $academyId)->first();
    }

    /**
     * Get preparation minutes from academy settings
     */
    protected function getPreparationMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_preparation_minutes ?? 10;
    }

    /**
     * Get grace period minutes from academy settings
     */
    protected function getGracePeriodMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_late_tolerance_minutes ?? 15;
    }

    /**
     * Get buffer minutes from academy settings
     */
    protected function getBufferMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_buffer_minutes ?? 5;
    }

    /**
     * Get early join minutes from academy settings
     */
    protected function getEarlyJoinMinutes(BaseSession $session): int
    {
        $settings = $this->getAcademySettings($session);
        return $settings?->default_early_join_minutes ?? 15;
    }

    /**
     * Get max future hours for ongoing transition
     */
    protected function getMaxFutureHoursOngoing(): int
    {
        return 2; // Don't allow ongoing status for sessions more than 2 hours in future
    }

    /**
     * Get max future hours for ready transition
     */
    protected function getMaxFutureHours(): int
    {
        return 24; // Don't process sessions more than 24h in future
    }

    /**
     * Get session type identifier for logging/events
     */
    protected function getSessionType(BaseSession $session): string
    {
        if ($session instanceof QuranSession) {
            return 'quran';
        }
        if ($session instanceof AcademicSession) {
            return 'academic';
        }
        if ($session instanceof InteractiveCourseSession) {
            return 'interactive';
        }
        return 'unknown';
    }

    /**
     * Check if session is an individual (1-on-1) session
     */
    protected function isIndividualSession(BaseSession $session): bool
    {
        if ($session instanceof QuranSession || $session instanceof AcademicSession) {
            return $session->session_type === 'individual';
        }
        // Interactive course sessions are always group
        return false;
    }

    /**
     * Transition session from SCHEDULED to READY
     * Called when preparation time begins
     */
    public function transitionToReady(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            Log::warning('Cannot transition to READY: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
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
            'session_type' => $this->getSessionType($session),
            'scheduled_at' => $session->scheduled_at,
        ]);

        return true;
    }

    /**
     * Create meeting room for session when it becomes ready
     */
    private function createMeetingForSession(BaseSession $session): void
    {
        try {
            // Only create if meeting doesn't already exist
            if ($session->meeting_room_name) {
                Log::info('Meeting already exists for session', [
                    'session_id' => $session->id,
                    'session_type' => $this->getSessionType($session),
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
                'session_type' => $this->getSessionType($session),
                'room_name' => $session->meeting_room_name,
                'meeting_url' => $meetingUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create meeting for ready session', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Transition session from READY to ONGOING
     * Called when first participant joins the meeting
     */
    public function transitionToOngoing(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::READY) {
            Log::warning('Cannot transition to ONGOING: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        // Check if session has scheduled time
        if (!$session->scheduled_at) {
            Log::warning('Cannot transition to ONGOING: no scheduled time', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
            ]);

            return false;
        }

        // Validate session time has arrived (with early grace period from settings)
        $allowEarlyJoinMinutes = $this->getEarlyJoinMinutes($session);
        $earliestJoinTime = $session->scheduled_at->copy()->subMinutes($allowEarlyJoinMinutes);

        if (now()->lt($earliestJoinTime)) {
            Log::warning('Cannot transition to ONGOING: session time has not arrived', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'scheduled_at' => $session->scheduled_at,
                'current_time' => now(),
                'earliest_join_time' => $earliestJoinTime,
            ]);

            return false;
        }

        // Safety check - don't allow transition for sessions too far in future
        $maxFutureHours = $this->getMaxFutureHoursOngoing();
        if ($session->scheduled_at->gt(now()->addHours($maxFutureHours))) {
            Log::warning('Cannot transition to ONGOING: session too far in future', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
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
            'session_type' => $this->getSessionType($session),
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Transition session from ONGOING to COMPLETED
     * Called when session naturally ends or teacher marks it complete
     */
    public function transitionToCompleted(BaseSession $session): bool
    {
        if (!in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            Log::warning('Cannot transition to COMPLETED: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'current_status' => $session->status->value,
            ]);

            return false;
        }

        $session->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => now(),
            'actual_duration_minutes' => $this->calculateActualDuration($session),
        ]);

        // Dispatch event to finalize attendance (decoupled from MeetingAttendanceService)
        SessionCompletedEvent::dispatch($session, $this->getSessionType($session));

        Log::info('SessionCompletedEvent dispatched for attendance finalization', [
            'session_id' => $session->id,
            'session_type' => $this->getSessionType($session),
        ]);

        // Close the LiveKit meeting room to prevent new joins
        if ($session->meeting_room_name) {
            try {
                $session->endMeeting();

                Log::info('Meeting room closed on session completion', [
                    'session_id' => $session->id,
                    'session_type' => $this->getSessionType($session),
                    'room_name' => $session->meeting_room_name,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to close meeting room on completion', [
                    'session_id' => $session->id,
                    'session_type' => $this->getSessionType($session),
                    'room_name' => $session->meeting_room_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Handle subscription counting for individual sessions
        if ($this->isIndividualSession($session)) {
            $this->handleIndividualSessionCompletion($session);
        }

        // Send notifications when session completes
        $this->sendSessionCompletedNotifications($session);

        Log::info('Session transitioned to COMPLETED', [
            'session_id' => $session->id,
            'session_type' => $this->getSessionType($session),
            'ended_at' => now(),
            'actual_duration' => $session->actual_duration_minutes,
        ]);

        return true;
    }

    /**
     * Transition session to CANCELLED
     * Called when teacher/admin cancels the session
     */
    public function transitionToCancelled(BaseSession $session, ?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (!in_array($session->status, [SessionStatus::SCHEDULED, SessionStatus::READY])) {
            Log::warning('Cannot transition to CANCELLED: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
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
            'session_type' => $this->getSessionType($session),
            'reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        return true;
    }

    /**
     * Transition session to ABSENT (individual sessions only)
     * Called when student doesn't join within grace period
     */
    public function transitionToAbsent(BaseSession $session): bool
    {
        if (!$this->isIndividualSession($session)) {
            Log::warning('Cannot transition to ABSENT: not an individual session', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
            ]);

            return false;
        }

        if (!in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            Log::warning('Cannot transition to ABSENT: invalid current status', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
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

        // Absent sessions count towards subscription (by design)
        if (method_exists($session, 'updateSubscriptionUsage')) {
            $session->updateSubscriptionUsage();
        }

        // Send absent notifications
        $this->sendAbsentNotifications($session);

        Log::info('Session transitioned to ABSENT', [
            'session_id' => $session->id,
            'session_type' => $this->getSessionType($session),
            'student_id' => $session->student_id ?? null,
        ]);

        return true;
    }

    /**
     * Check if session should transition to READY
     */
    public function shouldTransitionToReady(BaseSession $session): bool
    {
        if ($session->status !== SessionStatus::SCHEDULED) {
            return false;
        }

        // Check if session has scheduled time
        if (!$session->scheduled_at) {
            return false;
        }

        // Get preparation minutes from academy settings
        $preparationMinutes = $this->getPreparationMinutes($session);
        $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);

        // Add safety check - don't process sessions too far in future
        $maxFutureTime = now()->addHours($this->getMaxFutureHours());

        if ($session->scheduled_at->gt($maxFutureTime)) {
            return false; // Skip sessions too far in future
        }

        // Also don't process sessions older than 24 hours
        $minPastTime = now()->subHours(24);
        if ($session->scheduled_at->lt($minPastTime)) {
            return false; // Skip very old sessions
        }

        return now()->gte($preparationTime);
    }

    /**
     * Check if session should transition to ABSENT (individual only)
     */
    public function shouldTransitionToAbsent(BaseSession $session): bool
    {
        if (!$this->isIndividualSession($session)) {
            return false;
        }

        // Check if session is in READY or ONGOING status
        if (!in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return false;
        }

        // Check if session has scheduled time
        if (!$session->scheduled_at) {
            return false;
        }

        // Get grace period from academy settings
        $graceMinutes = $this->getGracePeriodMinutes($session);
        $graceDeadline = $session->scheduled_at->copy()->addMinutes($graceMinutes);

        // Check if no student participation (no meeting attendances with duration > 0)
        $hasStudentParticipation = $session->meetingAttendances()
            ->where('user_type', 'student')
            ->where('total_duration_minutes', '>', 0)
            ->exists();

        // Session should be marked absent if:
        // 1. Grace period has passed, AND
        // 2. No student participation
        return now()->gt($graceDeadline) && !$hasStudentParticipation;
    }

    /**
     * Check if session should auto-complete
     */
    public function shouldAutoComplete(BaseSession $session): bool
    {
        if (!in_array($session->status, [SessionStatus::ONGOING, SessionStatus::READY])) {
            return false;
        }

        // Check if session has scheduled time
        if (!$session->scheduled_at) {
            return false;
        }

        // Get buffer minutes from academy settings
        $endingBufferMinutes = $this->getBufferMinutes($session);
        $durationMinutes = $session->duration_minutes ?? 60;
        $autoCompleteTime = $session->scheduled_at
            ->copy()
            ->addMinutes($durationMinutes)
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
                        continue; // Don't process further in same cycle
                    }
                }

                // Check for ABSENT transition FIRST (individual sessions only)
                // This must come before auto-completion to prevent completed sessions
                // when student was actually absent
                if ($this->shouldTransitionToAbsent($session)) {
                    if ($this->transitionToAbsent($session)) {
                        $results['transitions_to_absent']++;
                        continue; // Don't check auto-complete if marked absent
                    }
                }
                // Only check for auto-completion if session is not absent
                elseif ($this->shouldAutoComplete($session)) {
                    if ($this->transitionToCompleted($session)) {
                        $results['transitions_to_completed']++;
                    }
                }

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'session_type' => $this->getSessionType($session),
                    'error' => $e->getMessage(),
                ];

                Log::error('Error processing session status transition', [
                    'session_id' => $session->id,
                    'session_type' => $this->getSessionType($session),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Calculate actual session duration
     */
    private function calculateActualDuration(BaseSession $session): int
    {
        if (!$session->started_at) {
            return 0;
        }

        $endTime = $session->ended_at ?? now();

        return $session->started_at->diffInMinutes($endTime);
    }

    /**
     * Handle individual session completion logic
     */
    private function handleIndividualSessionCompletion(BaseSession $session): void
    {
        // Check StudentSessionReport first (most reliable source)
        $studentReport = \App\Models\StudentSessionReport::where('session_id', $session->id)
            ->where('student_id', $session->student_id)
            ->first();

        if ($studentReport) {
            // Use StudentSessionReport data (teacher-verified, comprehensive)
            if ($studentReport->attendance_status === 'absent') {
                $session->update(['status' => SessionStatus::ABSENT]);
                Log::info('Individual session marked as ABSENT based on StudentSessionReport', [
                    'session_id' => $session->id,
                    'session_type' => $this->getSessionType($session),
                    'student_id' => $session->student_id,
                    'report_status' => $studentReport->attendance_status,
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
                'session_type' => $this->getSessionType($session),
                'student_id' => $session->student_id,
            ]);
        }

        // Update subscription usage for completed sessions
        if (method_exists($session, 'updateSubscriptionUsage')) {
            $session->updateSubscriptionUsage();
        }
    }

    /**
     * Record absent status in meeting attendance
     */
    private function recordAbsentStatus(BaseSession $session): void
    {
        if (!$this->isIndividualSession($session) || !$session->student_id) {
            return;
        }

        $attendance = $session->meetingAttendances()
            ->where('user_id', $session->student_id)
            ->first();

        if (!$attendance) {
            // Create absent attendance record
            MeetingAttendance::create([
                'session_id' => $session->id,
                'user_id' => $session->student_id,
                'user_type' => 'student',
                'session_type' => $this->isIndividualSession($session) ? 'individual' : 'group',
                'attendance_status' => 'absent',
                'attendance_percentage' => 0,
                'total_duration_minutes' => 0,
                'is_calculated' => true,
                'attendance_calculated_at' => now(),
            ]);
        } else {
            $attendance->update([
                'attendance_status' => 'absent',
                'is_calculated' => true,
                'attendance_calculated_at' => now(),
            ]);
        }
    }

    /**
     * Send notifications when session becomes ready
     */
    private function sendSessionReadyNotifications(BaseSession $session): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);

            // Get participants based on session type
            if ($session instanceof QuranSession) {
                $this->sendQuranSessionReadyNotifications($session, $notificationService);
            } elseif ($session instanceof AcademicSession) {
                $this->sendAcademicSessionReadyNotifications($session, $notificationService);
            } elseif ($session instanceof InteractiveCourseSession) {
                $this->sendInteractiveSessionReadyNotifications($session, $notificationService);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send session ready notifications', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send ready notifications for Quran sessions
     */
    private function sendQuranSessionReadyNotifications(QuranSession $session, $notificationService): void
    {
        try {
            $parentNotificationService = app(\App\Services\ParentNotificationService::class);

            if ($session->session_type === 'individual' && $session->student) {
                $notificationService->sendSessionReminderNotification($session, $session->student);
                $parentNotificationService->sendSessionReminder($session);
            } elseif ($session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $notificationService->sendSessionReminderNotification($session, $student->user);
                    }
                }
            }

            // Notify teacher
            if ($session->teacher) {
                $notificationService->send(
                    $session->teacher,
                    \App\Enums\NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $session->title ?? 'جلسة قرآنية'],
                    '/teacher/session-detail/' . $session->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Quran session ready notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send ready notifications for Academic sessions
     */
    private function sendAcademicSessionReadyNotifications(AcademicSession $session, $notificationService): void
    {
        try {
            $parentNotificationService = app(\App\Services\ParentNotificationService::class);

            if ($session->student) {
                $notificationService->sendSessionReminderNotification($session, $session->student);
                $parentNotificationService->sendSessionReminder($session);
            }

            // Notify teacher
            if ($session->academicTeacher?->user) {
                $notificationService->send(
                    $session->academicTeacher->user,
                    \App\Enums\NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $session->title ?? 'جلسة أكاديمية'],
                    '/academic-teacher/session-detail/' . $session->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Academic session ready notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send ready notifications for Interactive course sessions
     */
    private function sendInteractiveSessionReadyNotifications(InteractiveCourseSession $session, $notificationService): void
    {
        try {
            // Notify enrolled students
            if ($session->course && $session->course->enrollments) {
                foreach ($session->course->enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $notificationService->send(
                            $enrollment->user,
                            \App\Enums\NotificationType::SESSION_REMINDER,
                            [
                                'session_title' => $session->title ?? $session->course->title,
                                'session_number' => $session->session_number,
                            ],
                            '/student/courses/' . $session->course_id . '/sessions/' . $session->id
                        );
                    }
                }
            }

            // Notify teacher
            if ($session->course?->assignedTeacher?->user) {
                $notificationService->send(
                    $session->course->assignedTeacher->user,
                    \App\Enums\NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $session->title ?? $session->course?->title],
                    '/academic-teacher/courses/' . $session->course_id . '/sessions/' . $session->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Interactive session ready notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when session starts
     */
    private function sendSessionStartedNotifications(BaseSession $session): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $sessionType = $this->getSessionType($session);
            $sessionTitle = $session->title ?? ($sessionType === 'quran' ? 'جلسة قرآنية' : 'جلسة أكاديمية');

            // Individual session - notify student
            if ($this->isIndividualSession($session) && $session->student) {
                $notificationService->send(
                    $session->student,
                    \App\Enums\NotificationType::SESSION_STARTED,
                    ['session_title' => $sessionTitle],
                    '/student/session-detail/' . $session->id
                );
            }
            // Group Quran session - notify circle students
            elseif ($session instanceof QuranSession && $session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $notificationService->send(
                            $student->user,
                            \App\Enums\NotificationType::SESSION_STARTED,
                            ['session_title' => $sessionTitle],
                            '/student/session-detail/' . $session->id
                        );
                    }
                }
            }
            // Interactive course session - notify enrolled students
            elseif ($session instanceof InteractiveCourseSession && $session->course) {
                foreach ($session->course->enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $notificationService->send(
                            $enrollment->user,
                            \App\Enums\NotificationType::SESSION_STARTED,
                            ['session_title' => $sessionTitle],
                            '/student/courses/' . $session->course_id . '/sessions/' . $session->id
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session started notifications', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when session completes
     */
    private function sendSessionCompletedNotifications(BaseSession $session): void
    {
        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $sessionType = $this->getSessionType($session);
            $sessionTitle = $session->title ?? ($sessionType === 'quran' ? 'جلسة قرآنية' : 'جلسة أكاديمية');

            // Individual session - notify student
            if ($this->isIndividualSession($session) && $session->student) {
                $notificationService->send(
                    $session->student,
                    \App\Enums\NotificationType::SESSION_COMPLETED,
                    ['session_title' => $sessionTitle],
                    '/student/session-detail/' . $session->id
                );
            }
            // Group Quran session - notify circle students
            elseif ($session instanceof QuranSession && $session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $notificationService->send(
                            $student->user,
                            \App\Enums\NotificationType::SESSION_COMPLETED,
                            ['session_title' => $sessionTitle],
                            '/student/session-detail/' . $session->id
                        );
                    }
                }
            }
            // Interactive course session - notify enrolled students
            elseif ($session instanceof InteractiveCourseSession && $session->course) {
                foreach ($session->course->enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $notificationService->send(
                            $enrollment->user,
                            \App\Enums\NotificationType::SESSION_COMPLETED,
                            ['session_title' => $sessionTitle],
                            '/student/courses/' . $session->course_id . '/sessions/' . $session->id
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session completed notifications', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when student is marked absent
     */
    private function sendAbsentNotifications(BaseSession $session): void
    {
        if (!$this->isIndividualSession($session) || !$session->student) {
            return;
        }

        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $parentNotificationService = app(\App\Services\ParentNotificationService::class);
            $sessionType = $this->getSessionType($session);
            $sessionTitle = $session->title ?? ($sessionType === 'quran' ? 'جلسة قرآنية' : 'جلسة أكاديمية');

            // Notify student
            $notificationService->send(
                $session->student,
                \App\Enums\NotificationType::ATTENDANCE_MARKED_ABSENT,
                [
                    'session_title' => $sessionTitle,
                    'date' => $session->scheduled_at->format('Y-m-d'),
                ],
                '/student/session-detail/' . $session->id,
                [],
                true // important
            );

            // Notify parents
            $student = $session->student;
            $parents = $parentNotificationService->getParentsForStudent($student);
            foreach ($parents as $parent) {
                $notificationService->send(
                    $parent->user,
                    \App\Enums\NotificationType::ATTENDANCE_MARKED_ABSENT,
                    [
                        'child_name' => $student->name,
                        'session_title' => $sessionTitle,
                        'date' => $session->scheduled_at->format('Y-m-d'),
                    ],
                    route('parent.sessions.show', ['sessionType' => $sessionType, 'session' => $session->id]),
                    ['child_id' => $student->id, 'session_id' => $session->id],
                    true // important
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send absent notifications', [
                'session_id' => $session->id,
                'session_type' => $this->getSessionType($session),
                'student_id' => $session->student_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
