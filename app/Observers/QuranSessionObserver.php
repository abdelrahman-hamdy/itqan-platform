<?php

namespace App\Observers;

use App\Enums\TrialRequestStatus;
use App\Models\QuranSession;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use App\Services\TrialRequestSyncService;

class QuranSessionObserver
{
    public function __construct(
        protected TrialRequestSyncService $trialSyncService,
        protected NotificationService $notificationService,
        protected ParentNotificationService $parentNotificationService
    ) {}

    /**
     * Handle the QuranSession "created" event.
     */
    public function created(QuranSession $quranSession): void
    {
        // Link trial session to its request and sync initial status
        if ($quranSession->session_type === 'trial') {
            $this->trialSyncService->linkSessionToRequest($quranSession);
        }
    }

    /**
     * Handle the QuranSession "updated" event.
     */
    public function updated(QuranSession $quranSession): void
    {
        // Sync trial request status when session status changes
        if ($quranSession->session_type === 'trial' && $quranSession->wasChanged('status')) {
            $this->trialSyncService->syncStatus($quranSession);
        }

        // Send homework assigned notifications
        $this->checkHomeworkAssigned($quranSession);
    }

    /**
     * Check if homework was just assigned and send notifications
     */
    private function checkHomeworkAssigned(QuranSession $quranSession): void
    {
        // Check if any homework field was just assigned
        $homeworkAssigned = false;
        $homeworkType = null;

        if ($quranSession->isDirty('homework_assigned') && $quranSession->homework_assigned === true) {
            $homeworkAssigned = true;
        }

        if (! $homeworkAssigned) {
            return;
        }

        try {
            // Get student(s)
            if ($quranSession->session_type === 'individual' && $quranSession->student) {
                $student = $quranSession->student;

                // Send notification to student
                $this->notificationService->send(
                    $student,
                    \App\Enums\NotificationType::HOMEWORK_ASSIGNED,
                    [
                        'session_title' => $quranSession->title ?? 'جلسة قرآنية',
                        'teacher_name' => $quranSession->quranTeacher?->user->name ?? 'المعلم',
                        'due_date' => '',
                    ],
                    route('student.homework.view', ['id' => $quranSession->id, 'type' => 'quran']),
                    [
                        'session_id' => $quranSession->id,
                    ]
                );

                // Also notify parents
                $this->parentNotificationService->sendHomeworkAssigned(
                    (object) [
                        'student_id' => $student->id,
                        'title' => 'واجب قرآني',
                        'due_date' => null,
                    ]
                );
            }

            \Log::info('Quran homework assigned notifications sent', [
                'session_id' => $quranSession->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send Quran homework assigned notifications', [
                'session_id' => $quranSession->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the QuranSession "deleted" event.
     */
    public function deleted(QuranSession $quranSession): void
    {
        // If trial session is deleted, update trial request status to cancelled
        if ($quranSession->session_type === 'trial' && $quranSession->trialRequest) {
            $quranSession->trialRequest->update([
                'status' => TrialRequestStatus::CANCELLED,
            ]);
        }
    }

    /**
     * Handle the QuranSession "restored" event.
     */
    public function restored(QuranSession $quranSession): void
    {
        // Resync status when session is restored
        if ($quranSession->session_type === 'trial') {
            $this->trialSyncService->syncStatus($quranSession);
        }
    }

    /**
     * Handle the QuranSession "force deleted" event.
     */
    public function forceDeleted(QuranSession $quranSession): void
    {
        //
    }
}
