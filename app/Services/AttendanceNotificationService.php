<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Attendance Notification Service
 *
 * Handles notification dispatching for attendance-related events:
 * - Attendance marked notifications to students
 * - Parent notifications for student attendance
 * - WebSocket broadcasts for real-time updates
 */
class AttendanceNotificationService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected ParentNotificationService $parentNotificationService
    ) {}

    /**
     * Send attendance notifications after calculation
     */
    public function sendAttendanceNotifications(MeetingAttendance $attendance): void
    {
        try {
            $user = User::find($attendance->user_id);
            if (! $user || $attendance->user_type !== 'student') {
                return;
            }

            // Send notification to student
            $this->notificationService->sendAttendanceMarkedNotification(
                $attendance,
                $user,
                $attendance->attendance_status ?? AttendanceStatus::ATTENDED->value
            );

            // Send notifications to parents
            $this->sendParentNotifications($attendance, $user);

        } catch (\Exception $e) {
            Log::error('Failed to send attendance notification', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send attendance notifications to all parents of a student
     */
    protected function sendParentNotifications(MeetingAttendance $attendance, User $student): void
    {
        try {
            $parents = $this->parentNotificationService->getParentsForStudent($student);

            foreach ($parents as $parent) {
                $status = $attendance->attendance_status ?? AttendanceStatus::ATTENDED->value;
                $notificationType = match ($status) {
                    AttendanceStatus::ATTENDED->value => \App\Enums\NotificationType::ATTENDANCE_MARKED_PRESENT,
                    AttendanceStatus::ABSENT->value => \App\Enums\NotificationType::ATTENDANCE_MARKED_ABSENT,
                    AttendanceStatus::LATE->value => \App\Enums\NotificationType::ATTENDANCE_MARKED_LATE,
                    AttendanceStatus::LEFT->value => \App\Enums\NotificationType::ATTENDANCE_MARKED_LATE, // Left early treated as late for notifications
                    default => \App\Enums\NotificationType::ATTENDANCE_MARKED_PRESENT,
                };

                $sessionType = $attendance->session->getMeetingType() ?? 'session';
                $routeName = $sessionType === 'quran' ? 'parent.sessions.show' : 'parent.sessions.show';

                $this->notificationService->send(
                    $parent->user,
                    $notificationType,
                    [
                        'child_name' => $student->name,
                        'session_title' => $attendance->session->title ?? 'الجلسة',
                        'date' => $attendance->session->scheduled_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
                    ],
                    route($routeName, [
                        'sessionType' => $sessionType,
                        'session' => $attendance->session_id,
                    ]),
                    [
                        'child_id' => $student->id,
                        'attendance_id' => $attendance->id,
                        'session_id' => $attendance->session_id,
                        'status' => $status,
                    ],
                    $status === AttendanceStatus::ABSENT->value // Mark absent as important
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send parent attendance notification', [
                'attendance_id' => $attendance->id,
                'user_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast attendance update via WebSocket
     */
    public function broadcastAttendanceUpdate(int $sessionId, int $userId, array $data): void
    {
        try {
            broadcast(new \App\Events\AttendanceUpdated($sessionId, $userId, $data))->toOthers();

            Log::debug('Attendance update broadcasted', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'event' => $data['status'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast attendance update', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
