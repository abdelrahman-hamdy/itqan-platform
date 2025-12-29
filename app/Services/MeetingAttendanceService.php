<?php

namespace App\Services;

use App\Contracts\MeetingAttendanceServiceInterface;
use App\Contracts\MeetingCapable;
use App\Enums\SessionStatus;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Meeting Attendance Service (Facade)
 *
 * This service acts as a facade coordinating between:
 * - AttendanceCalculationService: Pure attendance calculation and tracking
 * - AttendanceNotificationService: Notification dispatching
 * - UnifiedSessionStatusService: Session status transitions
 *
 * Maintains backward compatibility with existing code while delegating
 * to smaller, focused services.
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

        if (!$attendance) {
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
                'status' => 'joined',
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

        if (!$attendance) {
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
                'status' => 'left',
                'attendance_percentage' => $attendance->attendance_percentage ?? 0,
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handleUserJoinPolymorphic($session, User $user, string $sessionType): bool
    {
        $attendance = $this->calculationService->handleUserJoinPolymorphic($session, $user, $sessionType);

        if (!$attendance) {
            return false;
        }

        // For academic sessions, different status transition logic
        if ($sessionType === 'academic') {
            // If this is the first participant and session is READY, transition to ONGOING
            if ($session->status === SessionStatus::READY) {
                $session->update(['status' => SessionStatus::ONGOING]);
            }
        } else {
            // Existing Quran session logic
            if ($session->status === SessionStatus::READY) {
                $this->statusService->transitionToOngoing($session);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handleUserLeavePolymorphic($session, User $user, string $sessionType): bool
    {
        return $this->calculationService->handleUserLeavePolymorphic($session, $user, $sessionType) !== null;
    }

    /**
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function processCompletedSessions(Collection $sessions): array
    {
        return $this->calculationService->processCompletedSessions($sessions);
    }

    /**
     * {@inheritdoc}
     */
    public function handleReconnection(MeetingCapable $session, User $user): bool
    {
        return $this->calculationService->handleReconnection($session, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttendanceStatistics(MeetingCapable $session): array
    {
        return $this->calculationService->getAttendanceStatistics($session);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupOldAttendanceRecords(int $daysOld = 7): int
    {
        return $this->calculationService->cleanupOldAttendanceRecords($daysOld);
    }

    /**
     * {@inheritdoc}
     */
    public function recalculateAttendance(MeetingCapable $session): array
    {
        return $this->calculationService->recalculateAttendance($session);
    }

    /**
     * {@inheritdoc}
     */
    public function exportAttendanceData(MeetingCapable $session): array
    {
        return $this->calculationService->exportAttendanceData($session);
    }
}
