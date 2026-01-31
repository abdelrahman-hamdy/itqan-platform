<?php

namespace App\Services\Notification;

use App\Enums\AttendanceStatus;
use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds and sends session-related notifications.
 *
 * Handles notifications for session scheduling, reminders,
 * homework assignments, and attendance marking.
 */
class SessionNotificationBuilder
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
        private readonly NotificationUrlBuilder $urlBuilder
    ) {}

    /**
     * Send session scheduled notification.
     *
     * @param  Model  $session  The session that was scheduled
     * @param  User  $student  The student to notify
     */
    public function sendSessionScheduledNotification(Model $session, User $student): void
    {
        $sessionType = class_basename($session);
        $teacherName = $session->teacher?->full_name ?? '';

        $this->dispatcher->send(
            $student,
            NotificationType::SESSION_SCHEDULED,
            [
                'session_title' => $session->title ?? $sessionType,
                'teacher_name' => $teacherName,
                'start_time' => $session->scheduled_at?->format('Y-m-d H:i') ?? '',
                'session_type' => $sessionType,
            ],
            $this->urlBuilder->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
            ],
            true
        );
    }

    /**
     * Send session reminder notification.
     *
     * @param  Model  $session  The upcoming session
     * @param  User  $student  The student to remind
     * @param  int  $minutesBefore  Minutes before session starts
     */
    public function sendSessionReminderNotification(Model $session, User $student, int $minutesBefore = 30): void
    {
        $sessionType = class_basename($session);

        $this->dispatcher->send(
            $student,
            NotificationType::SESSION_REMINDER,
            [
                'session_title' => $session->title ?? $sessionType,
                'minutes' => $minutesBefore,
                'start_time' => $session->scheduled_at?->format('H:i') ?? '',
            ],
            $this->urlBuilder->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
            ],
            true
        );
    }

    /**
     * Send homework assigned notification.
     *
     * @param  Model  $session  The session with homework
     * @param  User  $student  The student to notify
     * @param  int|null  $homeworkId  Optional specific homework ID
     */
    public function sendHomeworkAssignedNotification(Model $session, User $student, ?int $homeworkId = null): void
    {
        $sessionType = class_basename($session);
        $actionUrl = $this->urlBuilder->getHomeworkUrl($homeworkId, $session, $student);

        $this->dispatcher->send(
            $student,
            NotificationType::HOMEWORK_ASSIGNED,
            [
                'session_title' => $session->title ?? $sessionType,
                'teacher_name' => $session->teacher?->full_name ?? '',
                'due_date' => $session->homework_due_date?->format('Y-m-d') ?? '',
            ],
            $actionUrl,
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'homework_id' => $homeworkId,
            ]
        );
    }

    /**
     * Send attendance marked notification.
     *
     * @param  Model  $attendance  The attendance record
     * @param  User  $student  The student to notify
     * @param  string|AttendanceStatus  $status  The attendance status
     */
    public function sendAttendanceMarkedNotification(Model $attendance, User $student, string|AttendanceStatus $status): void
    {
        $session = $attendance->attendanceable;
        $statusValue = $status instanceof AttendanceStatus ? $status->value : $status;

        $type = match ($statusValue) {
            AttendanceStatus::ATTENDED->value => NotificationType::ATTENDANCE_MARKED_PRESENT,
            AttendanceStatus::ABSENT->value => NotificationType::ATTENDANCE_MARKED_ABSENT,
            AttendanceStatus::LATE->value => NotificationType::ATTENDANCE_MARKED_LATE,
            AttendanceStatus::LEFT->value => NotificationType::ATTENDANCE_MARKED_LATE, // Left early treated as late for notifications
            default => NotificationType::ATTENDANCE_MARKED_PRESENT,
        };

        $this->dispatcher->send(
            $student,
            $type,
            [
                'session_title' => $session->title ?? class_basename($session),
                'date' => $session->scheduled_at?->format('Y-m-d') ?? '',
            ],
            $this->urlBuilder->getSessionUrl($session, $student),
            [
                'attendance_id' => $attendance->id,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'status' => $statusValue,
            ]
        );
    }

    /**
     * Send session cancelled notification.
     *
     * @param  Model  $session  The cancelled session
     * @param  User  $student  The student to notify
     * @param  string|null  $reason  Cancellation reason
     */
    public function sendSessionCancelledNotification(Model $session, User $student, ?string $reason = null): void
    {
        $sessionType = class_basename($session);

        $this->dispatcher->send(
            $student,
            NotificationType::SESSION_CANCELLED,
            [
                'session_title' => $session->title ?? $sessionType,
                'date' => $session->scheduled_at?->format('Y-m-d') ?? '',
                'time' => $session->scheduled_at?->format('H:i') ?? '',
                'reason' => $reason ?? '',
            ],
            $this->urlBuilder->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'cancellation_reason' => $reason,
            ],
            true
        );
    }

    /**
     * Send session rescheduled notification.
     *
     * @param  Model  $session  The rescheduled session
     * @param  User  $student  The student to notify
     * @param  \DateTimeInterface  $oldDateTime  The original date/time
     */
    public function sendSessionRescheduledNotification(
        Model $session,
        User $student,
        \DateTimeInterface $oldDateTime
    ): void {
        $sessionType = class_basename($session);

        $this->dispatcher->send(
            $student,
            NotificationType::SESSION_RESCHEDULED,
            [
                'session_title' => $session->title ?? $sessionType,
                'old_date' => $oldDateTime->format('Y-m-d'),
                'old_time' => $oldDateTime->format('H:i'),
                'new_date' => $session->scheduled_at?->format('Y-m-d') ?? '',
                'new_time' => $session->scheduled_at?->format('H:i') ?? '',
            ],
            $this->urlBuilder->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'old_datetime' => $oldDateTime->format('Y-m-d H:i:s'),
            ],
            true
        );
    }
}
