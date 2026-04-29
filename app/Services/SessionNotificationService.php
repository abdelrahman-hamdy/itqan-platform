<?php

namespace App\Services;

use App\Constants\DefaultAcademy;
use App\Enums\NotificationType;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\Notification\NotificationUrlBuilder;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Session Notification Service
 *
 * Handles all session-related notifications to students, teachers, and parents.
 * Extracted from UnifiedSessionStatusService for better separation of concerns.
 *
 * TIMEZONE HANDLING:
 * All times are stored in UTC. This service converts them to academy
 * timezone for display in notifications.
 */
class SessionNotificationService
{
    public function __construct(
        protected SessionSettingsService $settingsService,
        protected NotificationService $notificationService,
        protected ParentNotificationService $parentNotificationService,
        protected NotificationUrlBuilder $urlBuilder,
    ) {}

    /**
     * Build the metadata payload that lets mobile FCM and the in-app
     * notifications list navigate to a session without parsing URLs.
     *
     * @return array<string, string>
     */
    private function sessionMetadata(BaseSession $session): array
    {
        return [
            'session_id' => (string) $session->id,
            'session_type' => $this->settingsService->getSessionType($session),
        ];
    }

    /**
     * Format datetime in academy timezone for notifications.
     */
    private function formatInAcademyTimezone(?Carbon $datetime, string $format = 'Y-m-d'): string
    {
        if (! $datetime) {
            return '';
        }

        $timezone = AcademyContextService::getTimezone();

        return $datetime->copy()->setTimezone($timezone)->format($format);
    }

    /**
     * Send notifications when session becomes ready
     */
    public function sendReadyNotifications(BaseSession $session): void
    {
        try {
            if ($session instanceof QuranSession) {
                $this->sendQuranSessionReadyNotifications($session);
            } elseif ($session instanceof AcademicSession) {
                $this->sendAcademicSessionReadyNotifications($session);
            } elseif ($session instanceof InteractiveCourseSession) {
                $this->sendInteractiveSessionReadyNotifications($session);
            }
        } catch (Exception $e) {
            Log::error('Failed to send session ready notifications', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send ready notifications for Quran sessions
     */
    protected function sendQuranSessionReadyNotifications(QuranSession $session): void
    {
        try {
            if ($session->session_type === 'individual' && $session->student) {
                $this->notificationService->sendSessionReminderNotification($session, $session->student);
                $this->parentNotificationService->sendSessionReminder($session);
            } elseif ($session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $this->notificationService->sendSessionReminderNotification($session, $student->user);
                    }
                }
            }

            // Notify teacher + supervisor
            if ($session->teacher) {
                $subdomain = $session->academy?->subdomain ?? DefaultAcademy::subdomain();
                $readyData = ['session_title' => $this->settingsService->getSessionTitle($session)];
                $metadata = $this->sessionMetadata($session);

                $this->notificationService->send(
                    $session->teacher,
                    NotificationType::MEETING_ROOM_READY,
                    $readyData,
                    route('teacher.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]),
                    $metadata,
                );

                $this->notificationService->notifySupervisorOfTeacher(
                    $session->teacher,
                    NotificationType::MEETING_ROOM_READY,
                    $readyData,
                    route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => 'quran', 'sessionId' => $session->id]),
                    $metadata,
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send Quran session ready notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send ready notifications for Academic sessions
     */
    protected function sendAcademicSessionReadyNotifications(AcademicSession $session): void
    {
        try {
            if ($session->student) {
                $this->notificationService->sendSessionReminderNotification($session, $session->student);
                $this->parentNotificationService->sendSessionReminder($session);
            }

            // Notify teacher + supervisor
            if ($session->academicTeacher?->user) {
                $subdomain = $session->academy?->subdomain ?? DefaultAcademy::subdomain();
                $readyData = ['session_title' => $this->settingsService->getSessionTitle($session)];
                $metadata = $this->sessionMetadata($session);

                $this->notificationService->send(
                    $session->academicTeacher->user,
                    NotificationType::MEETING_ROOM_READY,
                    $readyData,
                    route('teacher.academic-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]),
                    $metadata,
                );

                $this->notificationService->notifySupervisorOfTeacher(
                    $session->academicTeacher->user,
                    NotificationType::MEETING_ROOM_READY,
                    $readyData,
                    route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => 'academic', 'sessionId' => $session->id]),
                    $metadata,
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send Academic session ready notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send ready notifications for Interactive course sessions
     */
    protected function sendInteractiveSessionReadyNotifications(InteractiveCourseSession $session): void
    {
        try {
            $sessionTitle = $this->settingsService->getSessionTitle($session);
            $subdomain = $session->course?->academy?->subdomain ?? DefaultAcademy::subdomain();
            $metadata = $this->sessionMetadata($session);

            // Notify enrolled students
            if ($session->course && $session->course->enrollments) {
                $startTime = $this->formatInAcademyTimezone($session->scheduled_at, 'h:i A');

                foreach ($session->course->enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $this->notificationService->send(
                            $enrollment->user,
                            NotificationType::SESSION_REMINDER,
                            [
                                'session_title' => $sessionTitle,
                                'minutes' => 15, // Preparation time
                                'start_time' => $startTime,
                            ],
                            route('student.interactive-sessions.show', [
                                'subdomain' => $subdomain,
                                'session' => $session->id,
                            ]),
                            $metadata,
                        );

                        // Notify parent(s) for each enrolled student
                        $this->notifyParentsOfSession($session, $enrollment->user, $sessionTitle, NotificationType::SESSION_REMINDER_PARENT, ['minutes' => 15]);
                    }
                }
            }

            // Notify teacher + supervisor
            if ($session->course?->assignedTeacher?->user) {
                $this->notificationService->send(
                    $session->course->assignedTeacher->user,
                    NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $sessionTitle],
                    route('teacher.interactive-sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]),
                    $metadata,
                );

                $this->notificationService->notifySupervisorOfTeacher(
                    $session->course->assignedTeacher->user,
                    NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $sessionTitle],
                    route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => 'interactive', 'sessionId' => $session->id]),
                    $metadata,
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send Interactive session ready notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when session starts
     */
    public function sendStartedNotifications(BaseSession $session): void
    {
        $this->sendLifecycleNotifications(
            $session,
            NotificationType::SESSION_STARTED,
            NotificationType::SESSION_STARTED_PARENT,
            'Failed to send session started notifications',
        );
    }

    /**
     * Send notifications when session completes
     */
    public function sendCompletedNotifications(BaseSession $session): void
    {
        $this->sendLifecycleNotifications(
            $session,
            NotificationType::SESSION_COMPLETED,
            NotificationType::SESSION_COMPLETED_PARENT,
            'Failed to send session completed notifications',
        );
    }

    /**
     * Notify every student attending the session (1:1 student, group circle
     * roster, or interactive course enrolment) plus their parent(s). Used by
     * sendStartedNotifications / sendCompletedNotifications.
     */
    private function sendLifecycleNotifications(
        BaseSession $session,
        NotificationType $studentType,
        NotificationType $parentType,
        string $errorMessage,
    ): void {
        try {
            $sessionTitle = $this->settingsService->getSessionTitle($session);
            $metadata = $this->sessionMetadata($session);
            $messageData = ['session_title' => $sessionTitle];

            $students = $this->resolveSessionStudents($session);
            if ($students === []) {
                return;
            }

            // Same role + same session = identical URL for every recipient,
            // so resolve it once instead of paying ~5 hasRole() checks per
            // student inside `NotificationUrlBuilder::getSessionUrl()`.
            $url = $this->urlBuilder->getSessionUrl($session, $students[0]);

            foreach ($students as $student) {
                $this->notificationService->send(
                    $student,
                    $studentType,
                    $messageData,
                    $url,
                    $metadata,
                );
                $this->notifyParentsOfSession($session, $student, $sessionTitle, $parentType);
            }
        } catch (Exception $e) {
            Log::error($errorMessage, [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Collect every student-User attending a session, regardless of session
     * shape (individual, group circle, or interactive course enrolment).
     *
     * @return list<User>
     */
    private function resolveSessionStudents(BaseSession $session): array
    {
        if ($this->settingsService->isIndividualSession($session) && $session->student) {
            return [$session->student];
        }

        if ($session instanceof QuranSession && $session->session_type === 'group' && $session->circle) {
            return $session->circle->students
                ->pluck('user')
                ->filter()
                ->values()
                ->all();
        }

        if ($session instanceof InteractiveCourseSession && $session->course) {
            return $session->course->enrollments
                ->pluck('user')
                ->filter()
                ->values()
                ->all();
        }

        return [];
    }

    /**
     * Send notifications when student is marked absent
     */
    public function sendAbsentNotifications(BaseSession $session): void
    {
        if (! $this->settingsService->isIndividualSession($session) || ! $session->student) {
            return;
        }

        try {
            $sessionType = $this->settingsService->getSessionType($session);
            $sessionTitle = $this->settingsService->getSessionTitle($session);
            $metadata = $this->sessionMetadata($session);

            // Notify student
            $this->notificationService->send(
                $session->student,
                NotificationType::ATTENDANCE_MARKED_ABSENT,
                [
                    'session_title' => $sessionTitle,
                    'date' => $this->formatInAcademyTimezone($session->scheduled_at),
                ],
                $this->urlBuilder->getSessionUrl($session, $session->student),
                $metadata,
                true // important
            );

            // Notify parents
            $student = $session->student;
            $parents = $this->parentNotificationService->getParentsForStudent($student);
            foreach ($parents as $parent) {
                if (! $parent->user) {
                    continue;
                }
                $this->notificationService->send(
                    $parent->user,
                    NotificationType::ATTENDANCE_MARKED_ABSENT,
                    [
                        'child_name' => $student->name,
                        'session_title' => $sessionTitle,
                        'date' => $this->formatInAcademyTimezone($session->scheduled_at),
                    ],
                    $this->getParentSessionUrl($session, $sessionType),
                    array_merge($metadata, ['child_id' => (string) $student->id]),
                    true // important
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send absent notifications', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'student_id' => $session->student_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify a student's parent(s) about a session lifecycle event.
     */
    private function notifyParentsOfSession(
        BaseSession $session,
        User $student,
        string $sessionTitle,
        NotificationType $type,
        array $extraData = [],
    ): void {
        $parents = $this->parentNotificationService->getParentsForStudent($student);
        $url = $this->getParentSessionUrl($session, $this->settingsService->getSessionType($session));
        $metadata = array_merge($this->sessionMetadata($session), [
            'child_id' => (string) $student->id,
        ]);
        $data = array_merge([
            'student_name' => $student->name,
            'session_title' => $sessionTitle,
        ], $extraData);

        foreach ($parents as $parent) {
            if ($parent->user) {
                $this->notificationService->send($parent->user, $type, $data, $url, $metadata);
            }
        }
    }

    /**
     * Get URL for parent session view
     */
    private function getParentSessionUrl(BaseSession $session, string $sessionType): string
    {
        return route('parent.sessions.show', [
            'sessionType' => $sessionType,
            'session' => $session->id,
        ]);
    }
}
