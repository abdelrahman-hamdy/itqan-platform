<?php

namespace App\Observers;

use Exception;
use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AcademicSessionObserver
 *
 * Handles notifications when homework is assigned to academic sessions
 */
class AcademicSessionObserver
{
    /**
     * Handle the AcademicSession "updated" event.
     */
    public function updated(AcademicSession $session): void
    {
        // Handle session cancellation
        if ($session->wasChanged('status') && $session->status === SessionStatus::CANCELLED) {
            $this->handleCancellation($session);
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
     * Handle session cancellation side-effects
     */
    private function handleCancellation(AcademicSession $session): void
    {
        try {
            // Wrap both writes in a transaction so that a partial failure (e.g. reversal succeeds
            // but handleSessionCancelled fails) doesn't leave subscription counts inconsistent.
            DB::transaction(function () use ($session) {
                // 1. Reverse subscription usage if was counted
                if (method_exists($session, 'isSubscriptionCounted') && $session->isSubscriptionCounted()) {
                    $session->reverseSubscriptionUsage();
                }

                // 2. Update lesson remaining sessions (for individual sessions)
                if ($session->session_type === 'individual' && $session->academicIndividualLesson) {
                    $session->academicIndividualLesson->handleSessionCancelled();
                }
            });

            Log::info('Academic session cancellation handled', [
                'session_id' => $session->id,
                'session_type' => $session->session_type ?? 'individual',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to handle Academic session cancellation', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
                    'session_title' => $session->title ?? 'جلسة أكاديمية',
                    'teacher_name' => $session->academicTeacher?->user->name ?? 'المعلم',
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
                    'title' => $session->title ?? 'واجب جديد',
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
