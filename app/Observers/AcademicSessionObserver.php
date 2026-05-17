<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\SessionConsumption;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * AcademicSessionObserver
 *
 * Subscription + teacher-earning reversal lives in BaseSessionObserver.
 * This observer runs the Academic-specific tails (lesson cancellation
 * hook, homework assignment notifications).
 */
class AcademicSessionObserver
{
    /**
     * Handle the AcademicSession "updated" event.
     */
    public function updated(AcademicSession $session): void
    {
        if ($session->wasChanged('status')) {
            $previousStatus = BaseSessionObserver::resolvePreviousStatus($session);
            $newStatus = $session->status;

            if ($previousStatus === SessionStatus::COMPLETED
                && $newStatus !== SessionStatus::COMPLETED
                && $this->hasActiveConsumption($session)) {
                $this->cleanupLessonOnUncomplete($session);
            }
        }

        // Use wasChanged() (not isDirty) in the 'updated' observer: after save, isDirty() is always false.
        // Deduplicate: send one notification if ANY homework field changed to a truthy value.
        $homeworkDescriptionSet = $session->wasChanged('homework_description') && ! empty($session->homework_description);
        $homeworkFlagSet = $session->wasChanged('homework_assigned') && $session->homework_assigned === true;

        if ($homeworkDescriptionSet || $homeworkFlagSet) {
            $this->sendHomeworkAssignedNotifications($session);
        }
    }

    /**
     * Handle the AcademicSession "deleted" event.
     *
     * Subscription + earnings reversal is handled by BaseSessionObserver.
     * This handler only runs the Academic-specific lesson cleanup.
     */
    public function deleted(AcademicSession $session): void
    {
        if ($this->hasActiveConsumption($session)) {
            $this->cleanupLessonOnUncomplete($session);
        }
    }

    private function hasActiveConsumption(AcademicSession $session): bool
    {
        return SessionConsumption::query()
            ->where('session_id', $session->id)
            ->where('session_type', $session->getMorphClass())
            ->whereNull('reversed_at')
            ->exists();
    }

    private function cleanupLessonOnUncomplete(AcademicSession $session): void
    {
        try {
            if ($session->session_type === 'individual' && $session->academicIndividualLesson) {
                $session->academicIndividualLesson->handleSessionCancelled($session);
            }
        } catch (Exception $e) {
            Log::error('Failed to clean up academic lesson after uncomplete', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    /**
     * Send notifications when homework is assigned
     */
    private function sendHomeworkAssignedNotifications(AcademicSession $session): void
    {
        try {
            $student = $session->student;
            if (! $student) {
                return;
            }

            $notificationService = app(NotificationService::class);
            $parentNotificationService = app(ParentNotificationService::class);

            // Send notification to student
            $notificationService->send(
                $student,
                NotificationType::HOMEWORK_ASSIGNED,
                [
                    'session_title' => $session->title ?? __('notifications.academic_session_default'),
                    'teacher_name' => $session->academicTeacher?->user->name ?? __('notifications.teacher_default'),
                    'due_date' => $session->homework_due_date?->format('Y-m-d') ?? '',
                ],
                route('student.homework.view', ['id' => $session->id, 'type' => 'academic']),
                [
                    'session_id' => $session->id,
                ]
            );

            // Also notify parents
            $parentNotificationService->sendHomeworkAssigned(
                (object) [
                    'student_id' => $student->id,
                    'title' => $session->title ?? __('notifications.new_homework'),
                    'due_date' => $session->homework_due_date,
                ]
            );

            Log::info('Academic homework assigned notifications sent', [
                'session_id' => $session->id,
                'student_id' => $student->id,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send academic homework assigned notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
