<?php

namespace App\Observers;

use App\Models\AcademicSession;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
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
        // Check if homework was just assigned (homework_description changed from null/empty to filled)
        if ($session->isDirty('homework_description') && ! empty($session->homework_description)) {
            $this->sendHomeworkAssignedNotifications($session);
        }

        // Also check if homework_assigned flag was set to true
        if ($session->isDirty('homework_assigned') && $session->homework_assigned === true) {
            $this->sendHomeworkAssignedNotifications($session);
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
                \App\Enums\NotificationType::HOMEWORK_ASSIGNED,
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

        } catch (\Exception $e) {
            Log::error('Failed to send academic homework assigned notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
