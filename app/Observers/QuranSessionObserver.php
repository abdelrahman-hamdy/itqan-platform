<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Services\NotificationService;
use App\Services\ParentNotificationService;
use App\Services\TrialRequestSyncService;
use Exception;
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
     *
     * Subscription + teacher-earning reversal on COMPLETED→non-counting
     * flips lives in BaseSessionObserver; this handler only runs the
     * Quran-specific tails (trial sync, individualCircle/circle counts).
     */
    public function updated(QuranSession $quranSession): void
    {
        // Sync trial request status when session status changes
        if ($quranSession->session_type === 'trial' && $quranSession->wasChanged('status')) {
            $this->trialSyncService->syncStatus($quranSession);
        }

        if ($quranSession->wasChanged('status')) {
            $previousStatus = BaseSessionObserver::resolvePreviousStatus($quranSession);
            $newStatus = $quranSession->status;

            // Quran-specific tail when a counted session flips out of COMPLETED:
            // recompute circle/individualCircle session counts so they don't
            // include the now-uncounted session.
            if ($previousStatus === SessionStatus::COMPLETED
                && $newStatus !== SessionStatus::COMPLETED
                && $this->hasActiveConsumption($quranSession)) {
                $this->cleanupCircleCountsOnUncomplete($quranSession);
            }

            // Update circle session counts and progress when session is completed
            if ($newStatus === SessionStatus::COMPLETED) {
                if ($quranSession->session_type === 'individual' && $quranSession->individualCircle) {
                    $quranSession->individualCircle->loadMissing('subscription');
                    $quranSession->individualCircle->updateSessionCounts();
                    $quranSession->individualCircle->updateProgress();
                }
                if ($quranSession->session_type === 'circle' && $quranSession->circle) {
                    $quranSession->circle->updateSessionCounts();
                }
            }
        }

        // Send homework assigned notifications
        $this->checkHomeworkAssigned($quranSession);
    }

    private function cleanupCircleCountsOnUncomplete(QuranSession $session): void
    {
        try {
            if ($session->session_type === 'individual' && $session->individualCircle) {
                $session->individualCircle->loadMissing('subscription');
                $session->individualCircle->handleSessionCancelled();
            }

            if ($session->session_type === 'circle' && $session->circle) {
                $session->circle->updateSessionCounts();
            }
        } catch (Exception $e) {
            Log::error('Failed to clean up Quran circle counts after uncomplete', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
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

            Log::info('Quran homework assigned notifications sent', [
                'session_id' => $quranSession->id,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send Quran homework assigned notifications', [
                'session_id' => $quranSession->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    /**
     * Handle the QuranSession "deleted" event.
     *
     * Subscription + earnings reversal is handled by BaseSessionObserver.
     * This handler runs only the Quran-specific tails: trial request cancel,
     * circle counts, and scheduled-count resync.
     */
    public function deleted(QuranSession $quranSession): void
    {
        if ($quranSession->session_type === 'trial' && $quranSession->trialRequest) {
            $quranSession->trialRequest->update([
                'status' => TrialRequestStatus::CANCELLED,
            ]);
        }

        if ($this->hasActiveConsumption($quranSession)) {
            $this->cleanupCircleCountsOnUncomplete($quranSession);
        }

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
     * Sync subscription's total_sessions_scheduled count from actual session records,
     * scoped to the CURRENT cycle window. Sessions from archived cycles still link
     * to this subscription via quran_subscription_id, but they must not inflate the
     * current cycle's "scheduled" gauge — that's the cross-cycle interpolation the
     * business rule explicitly forbids.
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

        $query = QuranSession::where('quran_subscription_id', $subscription->id)
            ->whereNotIn('status', [SessionStatus::CANCELLED]);

        if ($subscription->starts_at) {
            $query->where('scheduled_at', '>=', $subscription->starts_at);
        }

        $count = $query->count();

        if ((int) $subscription->total_sessions_scheduled === $count) {
            return;
        }

        $subscription->updateQuietly(['total_sessions_scheduled' => $count]);
    }

    /**
     * Handle the QuranSession "force deleted" event.
     */
    public function forceDeleted(QuranSession $quranSession): void
    {
        //
    }

    private function hasActiveConsumption(QuranSession $session): bool
    {
        return SessionConsumption::query()
            ->where('session_id', $session->id)
            ->where('session_type', $session->getMorphClass())
            ->whereNull('reversed_at')
            ->exists();
    }
}
