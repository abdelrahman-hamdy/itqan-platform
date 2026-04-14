<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\TeacherEarning;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use Exception;
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
        $this->reverseSessionSideEffects($session, 'cancellation');
    }

    /**
     * Shared logic for reversing session side-effects (cancellation).
     */
    private function reverseSessionSideEffects(AcademicSession $session, string $action): void
    {
        try {
            DB::transaction(function () use ($session) {
                if ($session->isSubscriptionCounted()) {
                    $session->reverseSubscriptionUsage();
                }

                // Delete teacher earnings for cancelled session
                TeacherEarning::where('session_type', AcademicSession::class)
                    ->where('session_id', $session->id)
                    ->delete();

                if ($session->session_type === 'individual' && $session->academicIndividualLesson) {
                    $session->academicIndividualLesson->handleSessionCancelled($session);
                }
            });

            Log::info("Academic session {$action} handled", [
                'session_id' => $session->id,
                'session_type' => $session->session_type ?? 'individual',
            ]);
        } catch (Exception $e) {
            report($e);
            Log::error("Failed to handle Academic session {$action}", [
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
