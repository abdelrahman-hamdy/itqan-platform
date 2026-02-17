<?php

namespace App\Observers;

use Exception;
use App\Enums\TrialRequestStatus;
use App\Models\QuranTrialRequest;
use App\Services\StudentDashboardService;
use App\Services\TrialNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Observer for QuranTrialRequest model.
 *
 * Handles notifications for trial request lifecycle events:
 * - Status changes (scheduled, completed, cancelled)
 * - Cache invalidation for student dashboard
 */
class QuranTrialRequestObserver
{
    public function __construct(
        protected TrialNotificationService $notificationService,
        protected StudentDashboardService $dashboardService
    ) {}

    /**
     * Handle the QuranTrialRequest "created" event.
     *
     * Clears the student's dashboard cache so the new trial request appears immediately.
     */
    public function created(QuranTrialRequest $trialRequest): void
    {
        $this->clearStudentDashboardCache($trialRequest);
    }

    /**
     * Handle the QuranTrialRequest "updated" event.
     *
     * Sends notifications based on status changes and clears cache.
     */
    public function updated(QuranTrialRequest $trialRequest): void
    {
        // Always clear cache when trial request is updated
        $this->clearStudentDashboardCache($trialRequest);

        // Only process notifications if status actually changed
        if (! $trialRequest->wasChanged('status')) {
            return;
        }

        $newStatus = $trialRequest->status;
        $oldStatus = $trialRequest->getOriginal('status');

        // Convert string to enum if needed
        if (is_string($oldStatus)) {
            $oldStatus = TrialRequestStatus::tryFrom($oldStatus);
        }

        Log::info('Trial request status changed', [
            'trial_request_id' => $trialRequest->id,
            'old_status' => $oldStatus?->value ?? 'null',
            'new_status' => $newStatus->value,
        ]);

        try {
            match ($newStatus) {
                TrialRequestStatus::SCHEDULED => $this->handleScheduled($trialRequest),
                TrialRequestStatus::COMPLETED => $this->handleCompleted($trialRequest),
                TrialRequestStatus::CANCELLED => $this->handleCancelled($trialRequest),
                default => null, // No notification for other status changes
            };
        } catch (Exception $e) {
            Log::error('Failed to send trial request notification', [
                'trial_request_id' => $trialRequest->id,
                'new_status' => $newStatus->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle trial session scheduled.
     */
    protected function handleScheduled(QuranTrialRequest $trialRequest): void
    {
        // Reload to get the latest session
        $trialRequest->load('trialSession');

        if ($trialRequest->trialSession) {
            $this->notificationService->sendTrialScheduledNotification(
                $trialRequest,
                $trialRequest->trialSession
            );
        }
    }

    /**
     * Handle trial session completed.
     */
    protected function handleCompleted(QuranTrialRequest $trialRequest): void
    {
        $this->notificationService->sendTrialCompletedNotification($trialRequest);
    }

    /**
     * Handle trial request cancelled.
     */
    protected function handleCancelled(QuranTrialRequest $trialRequest): void
    {
        $this->notificationService->sendTrialCancelledNotification($trialRequest);
    }

    /**
     * Clear the student's dashboard cache so trial request changes appear immediately.
     */
    protected function clearStudentDashboardCache(QuranTrialRequest $trialRequest): void
    {
        try {
            $this->dashboardService->clearStudentCache(
                $trialRequest->student_id,
                $trialRequest->academy_id
            );

            Log::debug('Cleared student dashboard cache for trial request', [
                'trial_request_id' => $trialRequest->id,
                'student_id' => $trialRequest->student_id,
                'academy_id' => $trialRequest->academy_id,
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to clear student dashboard cache', [
                'trial_request_id' => $trialRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
