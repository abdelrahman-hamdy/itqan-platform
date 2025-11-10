<?php

namespace App\Observers;

use App\Models\AcademicSession;
use App\Services\AcademicProgressService;
use Illuminate\Support\Facades\Log;

class AcademicSessionObserver
{
    protected AcademicProgressService $progressService;

    public function __construct(AcademicProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Handle the AcademicSession "updated" event.
     * Update progress when session status changes to completed
     */
    public function updated(AcademicSession $session): void
    {
        // Check if status changed to completed
        if ($session->isDirty('status') && $session->status->value === 'completed') {
            $this->progressService->updateFromCompletedSession($session);
        }
    }

    /**
     * Handle the AcademicSession "deleted" event.
     */
    public function deleted(AcademicSession $session): void
    {
        // Recalculate metrics when a session is deleted
        if ($session->academic_subscription_id) {
            try {
                $subscription = $session->academicSubscription;
                if ($subscription) {
                    $progress = $this->progressService->getOrCreateProgress($subscription);
                    $this->progressService->recalculateMetrics($progress);
                }
            } catch (\Exception $e) {
                Log::error('Failed to recalculate progress after session deletion', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
