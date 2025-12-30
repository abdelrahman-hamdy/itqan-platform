<?php

namespace App\Services;

use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use App\Enums\SessionStatus;
use App\Enums\TrialRequestStatus;
use Illuminate\Support\Facades\Log;

/**
 * Service to synchronize QuranTrialRequest status with QuranSession status
 *
 * This ensures single source of truth (QuranSession) while keeping
 * QuranTrialRequest status updated for reporting purposes.
 */
class TrialRequestSyncService
{
    /**
     * Sync trial request status based on associated session status
     */
    public function syncStatus(QuranSession $session): void
    {
        // Only sync for trial sessions
        if ($session->session_type !== 'trial') {
            return;
        }

        // Skip if no associated trial request
        if (!$session->trial_request_id || !$session->trialRequest) {
            Log::warning('Trial session has no associated trial request', [
                'session_id' => $session->id,
                'session_code' => $session->session_code,
            ]);
            return;
        }

        $trialRequest = $session->trialRequest;
        $oldStatus = $trialRequest->status;

        // Map session status to trial request status
        $newStatus = $this->mapSessionStatusToRequestStatus($session->status);

        // Only update if status changed
        if ($newStatus && $newStatus !== $oldStatus) {
            $trialRequest->update(['status' => $newStatus]);

            Log::info('Trial request status synced', [
                'trial_request_id' => $trialRequest->id,
                'session_id' => $session->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'session_status' => $session->status->value,
            ]);
        }
    }

    /**
     * Map QuranSession status to QuranTrialRequest status
     */
    protected function mapSessionStatusToRequestStatus(SessionStatus $sessionStatus): ?TrialRequestStatus
    {
        return match($sessionStatus) {
            SessionStatus::SCHEDULED => TrialRequestStatus::SCHEDULED,
            SessionStatus::COMPLETED => TrialRequestStatus::COMPLETED,
            SessionStatus::CANCELLED => TrialRequestStatus::CANCELLED,
            SessionStatus::ABSENT => TrialRequestStatus::NO_SHOW,
            // Don't change status for other session states
            default => null,
        };
    }

    /**
     * Complete trial request when session is completed
     * Also handles completion metadata
     */
    public function completeTrialRequest(QuranSession $session, ?int $rating = null, ?string $feedback = null): void
    {
        if ($session->session_type !== 'trial' || !$session->trialRequest) {
            return;
        }

        $trialRequest = $session->trialRequest;

        // Complete with rating and feedback if provided
        $trialRequest->complete($rating, $feedback);

        Log::info('Trial request completed', [
            'trial_request_id' => $trialRequest->id,
            'session_id' => $session->id,
            'rating' => $rating,
        ]);
    }

    /**
     * Link a newly created trial session to its request
     */
    public function linkSessionToRequest(QuranSession $session): void
    {
        if ($session->session_type !== 'trial' || !$session->trial_request_id) {
            return;
        }

        $trialRequest = QuranTrialRequest::find($session->trial_request_id);

        if (!$trialRequest) {
            Log::error('Trial request not found for session', [
                'session_id' => $session->id,
                'trial_request_id' => $session->trial_request_id,
            ]);
            return;
        }

        // Link the session to the request (if not already linked)
        if (!$trialRequest->trial_session_id) {
            $trialRequest->update(['trial_session_id' => $session->id]);
        }

        // Sync the status
        $this->syncStatus($session);

        Log::info('Trial session linked to request', [
            'trial_request_id' => $trialRequest->id,
            'session_id' => $session->id,
        ]);
    }

    /**
     * Get scheduling information from associated session
     */
    public function getSchedulingInfo(QuranTrialRequest $trialRequest): ?array
    {
        $session = $trialRequest->trialSession;

        if (!$session) {
            return null;
        }

        return [
            'scheduled_at' => $session->scheduled_at,
            'duration_minutes' => $session->duration_minutes,
            'status' => $session->status->value,
            'meeting_url' => $session->getMeetingJoinUrl($trialRequest->student),
            'room_name' => $session->meeting?->room_name,
            'can_join' => $session->canJoinMeeting(),
        ];
    }
}
