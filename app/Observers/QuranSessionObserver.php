<?php

namespace App\Observers;

use App\Models\QuranSession;
use App\Services\TrialRequestSyncService;

class QuranSessionObserver
{
    protected TrialRequestSyncService $trialSyncService;

    public function __construct(TrialRequestSyncService $trialSyncService)
    {
        $this->trialSyncService = $trialSyncService;
    }

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
    }

    /**
     * Handle the QuranSession "deleted" event.
     */
    public function deleted(QuranSession $quranSession): void
    {
        // If trial session is deleted, update trial request status to cancelled
        if ($quranSession->session_type === 'trial' && $quranSession->trialRequest) {
            $quranSession->trialRequest->update([
                'status' => \App\Models\QuranTrialRequest::STATUS_CANCELLED,
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
