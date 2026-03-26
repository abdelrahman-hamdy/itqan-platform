<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use App\Services\TrialRequestSyncService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Sync subscription total_sessions_scheduled count
        $this->syncSubscriptionScheduledCount($quranSession);
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

        // Handle session cancellation
        if ($quranSession->wasChanged('status') && $quranSession->status === SessionStatus::CANCELLED) {
            $this->handleCancellation($quranSession);
        }

        // Handle session forgiveness (admin pardons absence)
        if ($quranSession->wasChanged('status') && $quranSession->status === SessionStatus::FORGIVEN) {
            $this->handleForgiveness($quranSession);
        }

        // Update circle session counts and progress when session is completed or absent
        if ($quranSession->wasChanged('status') && in_array($quranSession->status, [SessionStatus::COMPLETED, SessionStatus::ABSENT])) {
            if ($quranSession->session_type === 'individual' && $quranSession->individualCircle) {
                $quranSession->individualCircle->updateSessionCounts();
                $quranSession->individualCircle->updateProgress();
            }
            if ($quranSession->session_type === 'circle' && $quranSession->circle) {
                $quranSession->circle->updateSessionCounts();
            }
        }

        // Send homework assigned notifications
        $this->checkHomeworkAssigned($quranSession);
    }

    /**
     * Handle session cancellation side-effects
     */
    private function handleCancellation(QuranSession $session): void
    {
        $this->reverseSessionSideEffects($session, 'cancellation');
    }

    /**
     * Handle session forgiveness side-effects (admin pardons absence)
     */
    private function handleForgiveness(QuranSession $session): void
    {
        $this->reverseSessionSideEffects($session, 'forgiveness');
    }

    /**
     * Shared logic for reversing session side-effects (cancellation or forgiveness).
     */
    private function reverseSessionSideEffects(QuranSession $session, string $action): void
    {
        try {
            DB::transaction(function () use ($session) {
                if ($session->isSubscriptionCounted()) {
                    $session->reverseSubscriptionUsage();
                }

                if ($session->session_type === 'individual' && $session->individualCircle) {
                    $session->individualCircle->handleSessionCancelled();
                }

                if ($session->session_type === 'circle' && $session->circle) {
                    $session->circle->updateSessionCounts();
                }
            });

            Log::info("Quran session {$action} handled", [
                'session_id' => $session->id,
                'session_type' => $session->session_type,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to handle Quran session {$action}", [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);
        }
    }

    /**
     * Check if homework was just assigned and send notifications
     */
    private function checkHomeworkAssigned(QuranSession $quranSession): void
    {
        // Check if any homework field was just assigned
        $homeworkAssigned = false;

        if ($quranSession->wasChanged('homework_assigned') && $quranSession->homework_assigned === true) {
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
                // CQ-001: Use localized strings instead of hardcoded Arabic
                $this->notificationService->send(
                    $student,
                    NotificationType::HOMEWORK_ASSIGNED,
                    [
                        'session_title' => $quranSession->title ?? __('notifications.quran_session_default'),
                        'teacher_name' => $quranSession->quranTeacher?->user->name ?? __('notifications.teacher_default'),
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
                        'title' => __('notifications.quran_homework'),
                        'due_date' => null,
                    ]
                );
            }

            \Log::info('Quran homework assigned notifications sent', [
                'session_id' => $quranSession->id,
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to send Quran homework assigned notifications', [
                'session_id' => $quranSession->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
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

        // Sync subscription total_sessions_scheduled count
        $this->syncSubscriptionScheduledCount($quranSession);
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
     * Sync subscription's total_sessions_scheduled count from actual session records.
     */
    private function syncSubscriptionScheduledCount(QuranSession $session): void
    {
        if (! $session->quran_subscription_id) {
            return;
        }

        $subscription = QuranSubscription::find($session->quran_subscription_id);
        if (! $subscription) {
            return;
        }

        $count = QuranSession::where('quran_subscription_id', $subscription->id)
            ->whereNotIn('status', [SessionStatus::CANCELLED, SessionStatus::FORGIVEN])
            ->count();

        $subscription->updateQuietly(['total_sessions_scheduled' => $count]);
    }

    /**
     * Handle the QuranSession "force deleted" event.
     */
    public function forceDeleted(QuranSession $quranSession): void
    {
        //
    }
}
