<?php

namespace App\Services;

use App\Contracts\MeetingAttendanceServiceInterface;
use App\Contracts\MeetingCapable;
use App\Enums\MeetingEventType;
use App\Enums\SessionStatus;
use App\Models\User;

/**
 * Meeting Attendance Service
 *
 * Coordinates attendance tracking with side effects:
 * - AttendanceCalculationService: Pure attendance calculation and tracking
 * - AttendanceNotificationService: Notification dispatching and broadcasting
 * - UnifiedSessionStatusService: Session status transitions
 *
 * Only methods that add coordination logic beyond AttendanceCalculationService
 * belong here. For pure calculation (recalculate, statistics, export, cleanup),
 * use AttendanceCalculationService directly.
 */
class MeetingAttendanceService implements MeetingAttendanceServiceInterface
{
    public function __construct(
        protected AttendanceCalculationService $calculationService,
        protected AttendanceNotificationService $notificationService,
        protected UnifiedSessionStatusService $statusService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handleUserJoin(MeetingCapable $session, User $user): bool
    {
        $attendance = $this->calculationService->handleUserJoin($session, $user);

        if (! $attendance) {
            return false;
        }

        // If this is the first participant and session is READY, transition to ONGOING
        if ($session->status === SessionStatus::READY) {
            $this->statusService->transitionToOngoing($session);
        }

        // Broadcast attendance update
        $this->notificationService->broadcastAttendanceUpdate(
            $session->id,
            $user->id,
            [
                'is_currently_in_meeting' => true,
                'duration_minutes' => $attendance->getCurrentSessionDuration(),
                'join_count' => $attendance->join_count,
                'status' => MeetingEventType::JOINED->value,
                'attendance_percentage' => $attendance->attendance_percentage ?? 0,
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handleUserLeave(MeetingCapable $session, User $user): bool
    {
        $attendance = $this->calculationService->handleUserLeave($session, $user);

        if (! $attendance) {
            return false;
        }

        // Broadcast attendance update
        $this->notificationService->broadcastAttendanceUpdate(
            $session->id,
            $user->id,
            [
                'is_currently_in_meeting' => false,
                'duration_minutes' => $attendance->total_duration_minutes,
                'join_count' => $attendance->join_count,
                'leave_count' => $attendance->leave_count,
                'status' => MeetingEventType::LEFT->value,
                'attendance_percentage' => $attendance->attendance_percentage ?? 0,
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Calculates final attendance and dispatches notifications to students/parents.
     */
    public function calculateFinalAttendance(MeetingCapable $session): array
    {
        $results = $this->calculationService->calculateFinalAttendance($session);

        // Send notifications for each calculated attendance
        foreach ($results['attendances'] as $attendanceData) {
            try {
                $attendance = $session->meetingAttendances()
                    ->where('user_id', $attendanceData['user_id'])
                    ->first();

                if ($attendance) {
                    $this->notificationService->sendAttendanceNotifications($attendance);
                }
            } catch (\Exception $e) {
                // Continue processing other attendances even if notification fails
                continue;
            }
        }

        return $results;
    }
}
