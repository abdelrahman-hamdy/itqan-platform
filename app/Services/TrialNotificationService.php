<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use Carbon\Carbon;

/**
 * Service for handling trial session notifications.
 *
 * Handles all notification events in the trial session lifecycle:
 * - Trial request received (notify teacher)
 * - Trial approved (notify student)
 * - Trial scheduled (notify student + parent)
 * - Trial completed (notify both parties)
 *
 * TIMEZONE HANDLING:
 * All times are stored in UTC. This service converts them to academy
 * timezone for display in notifications.
 */
class TrialNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Format datetime in academy timezone for notifications.
     *
     * @param  string  $format  Default includes AM/PM
     */
    private function formatInAcademyTimezone(?Carbon $datetime, string $format = 'Y-m-d h:i A'): string
    {
        if (! $datetime) {
            return '';
        }

        $timezone = AcademyContextService::getTimezone();

        return $datetime->copy()->setTimezone($timezone)->format($format);
    }

    /**
     * Send notification to teacher when a new trial request is received.
     */
    public function sendTrialRequestReceivedNotification(QuranTrialRequest $trialRequest): void
    {
        if (! $trialRequest->teacher?->user) {
            return;
        }

        $subdomain = $trialRequest->academy?->subdomain ?? 'itqan-academy';

        $this->notificationService->send(
            $trialRequest->teacher->user,
            NotificationType::TRIAL_REQUEST_RECEIVED,
            [
                'student_name' => $trialRequest->student?->name ?? $trialRequest->student_name,
                'student_level' => $trialRequest->level_label,
                'preferred_time' => $trialRequest->time_label,
                'request_code' => $trialRequest->request_code,
            ],
            route('teacher.trial-sessions.show', [
                'subdomain' => $subdomain,
                'trialRequest' => $trialRequest->id,
            ]),
            ['trial_request_id' => $trialRequest->id],
            true // important
        );
    }

    /**
     * Send notification to student when their trial request is approved.
     */
    public function sendTrialApprovedNotification(QuranTrialRequest $trialRequest): void
    {
        if (! $trialRequest->student) {
            return;
        }

        $subdomain = $trialRequest->academy?->subdomain ?? 'itqan-academy';

        $this->notificationService->send(
            $trialRequest->student,
            NotificationType::TRIAL_REQUEST_APPROVED,
            [
                'teacher_name' => $trialRequest->teacher?->full_name ?? __('common.teacher'),
                'request_code' => $trialRequest->request_code,
            ],
            route('student.trial-requests.show', [
                'subdomain' => $subdomain,
                'trialRequest' => $trialRequest->id,
            ]),
            ['trial_request_id' => $trialRequest->id]
        );
    }

    /**
     * Send notification when a trial session is scheduled.
     * Notifies both student and parent (if exists).
     */
    public function sendTrialScheduledNotification(QuranTrialRequest $trialRequest, QuranSession $session): void
    {
        if (! $trialRequest->student) {
            return;
        }

        $subdomain = $trialRequest->academy?->subdomain ?? 'itqan-academy';
        $recipients = collect([$trialRequest->student]);

        // Also notify parent if exists
        if ($trialRequest->student->studentProfile?->parent?->user) {
            $recipients->push($trialRequest->student->studentProfile->parent->user);
        }

        $this->notificationService->send(
            $recipients,
            NotificationType::TRIAL_SESSION_SCHEDULED,
            [
                'teacher_name' => $trialRequest->teacher?->full_name ?? __('common.teacher'),
                'scheduled_date' => $this->formatInAcademyTimezone($session->scheduled_at, 'Y-m-d'),
                'scheduled_time' => $this->formatInAcademyTimezone($session->scheduled_at, 'h:i A'),
                'student_name' => $trialRequest->student->name,
                'request_code' => $trialRequest->request_code,
            ],
            route('student.trial-requests.show', [
                'subdomain' => $subdomain,
                'trialRequest' => $trialRequest->id,
            ]),
            [
                'trial_request_id' => $trialRequest->id,
                'session_id' => $session->id,
            ],
            true // important
        );
    }

    /**
     * Send notification when a trial session is completed.
     * Notifies student, parent, and teacher with role-specific messages.
     */
    public function sendTrialCompletedNotification(QuranTrialRequest $trialRequest): void
    {
        $subdomain = $trialRequest->academy?->subdomain ?? 'itqan-academy';

        // Notify student with STUDENT-specific type
        if ($trialRequest->student) {
            $this->notificationService->send(
                $trialRequest->student,
                NotificationType::TRIAL_SESSION_COMPLETED_STUDENT,
                [
                    'teacher_name' => $trialRequest->teacher?->full_name ?? __('common.teacher'),
                    'request_code' => $trialRequest->request_code,
                ],
                route('student.trial-requests.show', [
                    'subdomain' => $subdomain,
                    'trialRequest' => $trialRequest->id,
                ]),
                ['trial_request_id' => $trialRequest->id]
            );

            // Notify parent if exists
            $this->notifyParentOfTrialCompletion($trialRequest, $subdomain);
        }

        // Notify teacher with TEACHER-specific type
        if ($trialRequest->teacher?->user) {
            $this->notificationService->send(
                $trialRequest->teacher->user,
                NotificationType::TRIAL_SESSION_COMPLETED_TEACHER,
                [
                    'student_name' => $trialRequest->student?->name ?? $trialRequest->student_name,
                    'request_code' => $trialRequest->request_code,
                ],
                route('teacher.trial-sessions.show', [
                    'subdomain' => $subdomain,
                    'trialRequest' => $trialRequest->id,
                ]),
                ['trial_request_id' => $trialRequest->id]
            );
        }
    }

    /**
     * Notify parent when trial session is completed.
     */
    private function notifyParentOfTrialCompletion(QuranTrialRequest $trialRequest, string $subdomain): void
    {
        $parent = $trialRequest->student?->studentProfile?->parent?->user;
        if (! $parent) {
            return;
        }

        $this->notificationService->send(
            $parent,
            NotificationType::TRIAL_SESSION_COMPLETED_STUDENT,
            [
                'teacher_name' => $trialRequest->teacher?->full_name ?? __('common.teacher'),
                'student_name' => $trialRequest->student->name,
                'request_code' => $trialRequest->request_code,
            ],
            route('student.trial-requests.show', [
                'subdomain' => $subdomain,
                'trialRequest' => $trialRequest->id,
            ]),
            ['trial_request_id' => $trialRequest->id]
        );
    }

    /**
     * Send notification when a trial session is cancelled.
     * Notifies student and parent (if exists).
     */
    public function sendTrialCancelledNotification(QuranTrialRequest $trialRequest): void
    {
        if (! $trialRequest->student) {
            return;
        }

        $subdomain = $trialRequest->academy?->subdomain ?? 'itqan-academy';
        $recipients = collect([$trialRequest->student]);

        // Also notify parent if exists
        if ($trialRequest->student->studentProfile?->parent?->user) {
            $recipients->push($trialRequest->student->studentProfile->parent->user);
        }

        $this->notificationService->send(
            $recipients,
            NotificationType::TRIAL_SESSION_CANCELLED,
            [
                'teacher_name' => $trialRequest->teacher?->full_name ?? __('common.teacher'),
                'student_name' => $trialRequest->student->name,
                'request_code' => $trialRequest->request_code,
            ],
            route('student.trial-requests.index', [
                'subdomain' => $subdomain,
            ]),
            ['trial_request_id' => $trialRequest->id]
        );
    }
}
