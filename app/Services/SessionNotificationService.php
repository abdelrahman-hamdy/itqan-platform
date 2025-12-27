<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

/**
 * Session Notification Service
 *
 * Handles all session-related notifications to students, teachers, and parents.
 * Extracted from UnifiedSessionStatusService for better separation of concerns.
 */
class SessionNotificationService
{
    public function __construct(
        protected SessionSettingsService $settingsService,
        protected NotificationService $notificationService,
        protected ParentNotificationService $parentNotificationService
    ) {}

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
        } catch (\Exception $e) {
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

            // Notify teacher
            if ($session->teacher) {
                $this->notificationService->send(
                    $session->teacher,
                    NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $this->settingsService->getSessionTitle($session)],
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
    protected function sendAcademicSessionReadyNotifications(AcademicSession $session): void
    {
        try {
            if ($session->student) {
                $this->notificationService->sendSessionReminderNotification($session, $session->student);
                $this->parentNotificationService->sendSessionReminder($session);
            }

            // Notify teacher
            if ($session->academicTeacher?->user) {
                $this->notificationService->send(
                    $session->academicTeacher->user,
                    NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $this->settingsService->getSessionTitle($session)],
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
    protected function sendInteractiveSessionReadyNotifications(InteractiveCourseSession $session): void
    {
        try {
            // Notify enrolled students
            if ($session->course && $session->course->enrollments) {
                foreach ($session->course->enrollments as $enrollment) {
                    if ($enrollment->user) {
                        $this->notificationService->send(
                            $enrollment->user,
                            NotificationType::SESSION_REMINDER,
                            [
                                'session_title' => $this->settingsService->getSessionTitle($session),
                                'session_number' => $session->session_number,
                            ],
                            '/student/courses/' . $session->course_id . '/sessions/' . $session->id
                        );
                    }
                }
            }

            // Notify teacher
            if ($session->course?->assignedTeacher?->user) {
                $this->notificationService->send(
                    $session->course->assignedTeacher->user,
                    NotificationType::MEETING_ROOM_READY,
                    ['session_title' => $this->settingsService->getSessionTitle($session)],
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
    public function sendStartedNotifications(BaseSession $session): void
    {
        try {
            $sessionTitle = $this->settingsService->getSessionTitle($session);

            // Individual session - notify student
            if ($this->settingsService->isIndividualSession($session) && $session->student) {
                $this->notificationService->send(
                    $session->student,
                    NotificationType::SESSION_STARTED,
                    ['session_title' => $sessionTitle],
                    '/student/session-detail/' . $session->id
                );
            }
            // Group Quran session - notify circle students
            elseif ($session instanceof QuranSession && $session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $this->notificationService->send(
                            $student->user,
                            NotificationType::SESSION_STARTED,
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
                        $this->notificationService->send(
                            $enrollment->user,
                            NotificationType::SESSION_STARTED,
                            ['session_title' => $sessionTitle],
                            '/student/courses/' . $session->course_id . '/sessions/' . $session->id
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session started notifications', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when session completes
     */
    public function sendCompletedNotifications(BaseSession $session): void
    {
        try {
            $sessionTitle = $this->settingsService->getSessionTitle($session);

            // Individual session - notify student
            if ($this->settingsService->isIndividualSession($session) && $session->student) {
                $this->notificationService->send(
                    $session->student,
                    NotificationType::SESSION_COMPLETED,
                    ['session_title' => $sessionTitle],
                    '/student/session-detail/' . $session->id
                );
            }
            // Group Quran session - notify circle students
            elseif ($session instanceof QuranSession && $session->session_type === 'group' && $session->circle) {
                foreach ($session->circle->students as $student) {
                    if ($student->user) {
                        $this->notificationService->send(
                            $student->user,
                            NotificationType::SESSION_COMPLETED,
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
                        $this->notificationService->send(
                            $enrollment->user,
                            NotificationType::SESSION_COMPLETED,
                            ['session_title' => $sessionTitle],
                            '/student/courses/' . $session->course_id . '/sessions/' . $session->id
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send session completed notifications', [
                'session_id' => $session->id,
                'session_type' => $this->settingsService->getSessionType($session),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notifications when student is marked absent
     */
    public function sendAbsentNotifications(BaseSession $session): void
    {
        if (!$this->settingsService->isIndividualSession($session) || !$session->student) {
            return;
        }

        try {
            $sessionType = $this->settingsService->getSessionType($session);
            $sessionTitle = $this->settingsService->getSessionTitle($session);

            // Notify student
            $this->notificationService->send(
                $session->student,
                NotificationType::ATTENDANCE_MARKED_ABSENT,
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
            $parents = $this->parentNotificationService->getParentsForStudent($student);
            foreach ($parents as $parent) {
                $this->notificationService->send(
                    $parent->user,
                    NotificationType::ATTENDANCE_MARKED_ABSENT,
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
                'session_type' => $this->settingsService->getSessionType($session),
                'student_id' => $session->student_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
