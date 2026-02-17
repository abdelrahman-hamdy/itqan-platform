<?php

namespace App\Services\Notification;

use Carbon\Carbon;
use DateTimeInterface;
use App\Enums\AttendanceStatus;
use App\Enums\NotificationType;
use App\Models\User;
use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds and sends session-related notifications.
 *
 * Handles notifications for session scheduling, reminders,
 * homework assignments, and attendance marking.
 *
 * TIMEZONE HANDLING:
 * All times are stored in UTC. This builder converts them to academy
 * timezone for display in notifications using formatInAcademyTimezone().
 */
class SessionNotificationBuilder
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
        private readonly NotificationUrlBuilder $urlBuilder
    ) {}

    /**
     * Format a datetime in academy timezone for notification display.
     *
     * @param Carbon|DateTimeInterface|null $datetime
     * @param  string  $format  Format string (default includes AM/PM)
     * @return string Formatted time in academy timezone
     */
    private function formatInAcademyTimezone($datetime, string $format = 'Y-m-d h:i A'): string
    {
        if (! $datetime) {
            return '';
        }

        $timezone = AcademyContextService::getTimezone();

        return Carbon::parse($datetime)
            ->setTimezone($timezone)
            ->format($format);
    }

    /**
     * Format time only (with AM/PM) in academy timezone.
     */
    private function formatTimeOnly($datetime): string
    {
        return $this->formatInAcademyTimezone($datetime, 'h:i A');
    }

    /**
     * Format date only in academy timezone.
     */
    private function formatDateOnly($datetime): string
    {
        return $this->formatInAcademyTimezone($datetime, 'Y-m-d');
    }

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
                'start_time' => $this->formatInAcademyTimezone($session->scheduled_at),
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
                'start_time' => $this->formatTimeOnly($session->scheduled_at),
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
                'due_date' => $this->formatDateOnly($session->homework_due_date),
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
                'date' => $this->formatDateOnly($session->scheduled_at),
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
                'date' => $this->formatDateOnly($session->scheduled_at),
                'time' => $this->formatTimeOnly($session->scheduled_at),
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
     * @param DateTimeInterface $oldDateTime The original date/time
     */
    public function sendSessionRescheduledNotification(
        Model $session,
        User $student,
        DateTimeInterface $oldDateTime
    ): void {
        $sessionType = class_basename($session);

        $this->dispatcher->send(
            $student,
            NotificationType::SESSION_RESCHEDULED,
            [
                'session_title' => $session->title ?? $sessionType,
                'old_date' => $this->formatDateOnly($oldDateTime),
                'old_time' => $this->formatTimeOnly($oldDateTime),
                'new_date' => $this->formatDateOnly($session->scheduled_at),
                'new_time' => $this->formatTimeOnly($session->scheduled_at),
            ],
            $this->urlBuilder->getSessionUrl($session, $student),
            [
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'old_datetime' => $this->formatInAcademyTimezone($oldDateTime, 'Y-m-d H:i:s'),
            ],
            true
        );
    }
}
