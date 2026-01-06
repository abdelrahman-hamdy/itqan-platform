<?php

namespace App\Observers;

use App\Enums\SessionStatus;
use App\Jobs\CalculateSessionEarningsJob;
use App\Models\BaseSession;
use Illuminate\Support\Facades\Log;

/**
 * BaseSession Observer
 *
 * Automatically handles meeting creation when session status changes.
 * This ensures meetings are created immediately without waiting for cron jobs.
 */
class BaseSessionObserver
{
    /**
     * Handle the BaseSession "updating" event.
     * This runs BEFORE the model is saved to the database.
     */
    public function updating(BaseSession $session): void
    {
        // Check if status is changing to ready or ongoing
        if ($session->isDirty('status')) {
            $newStatus = $session->status;
            $oldStatus = $session->getOriginal('status');

            // Convert to enum if string
            if (is_string($newStatus)) {
                $newStatus = SessionStatus::from($newStatus);
            }
            if (is_string($oldStatus)) {
                $oldStatus = SessionStatus::from($oldStatus);
            }

            // If status is changing to ready or ongoing, ensure meeting exists
            if (in_array($newStatus, [SessionStatus::READY, SessionStatus::ONGOING])) {
                // Check if meeting room doesn't exist yet
                if (empty($session->meeting_room_name)) {
                    try {
                        Log::info('Auto-creating meeting room for session', [
                            'session_id' => $session->id,
                            'session_type' => $session->getMeetingType(),
                        ]);

                        // Generate meeting link (this creates the LiveKit room)
                        $session->generateMeetingLink();

                        Log::info('Meeting room created successfully', [
                            'session_id' => $session->id,
                            'room_name' => $session->meeting_room_name,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to auto-create meeting room', [
                            'session_id' => $session->id,
                            'error' => $e->getMessage(),
                        ]);

                        // Don't throw - let the status update proceed
                        // The cron job will retry meeting creation later
                    }
                } else {
                    Log::debug('Meeting room already exists for session', [
                        'session_id' => $session->id,
                        'room_name' => $session->meeting_room_name,
                    ]);
                }
            }
        }
    }

    /**
     * Handle the BaseSession "updated" event.
     * This runs AFTER the model is saved to the database.
     */
    public function updated(BaseSession $session): void
    {
        // CRITICAL: Regenerate meeting when session is rescheduled
        // This ensures the meeting data stays in sync with the session time
        if ($session->wasChanged('scheduled_at')) {
            $oldTime = $session->getOriginal('scheduled_at');
            $newTime = $session->scheduled_at;

            Log::info('Session rescheduled - checking if meeting needs update', [
                'session_id' => $session->id,
                'session_type' => $session->getMeetingType(),
                'has_meeting' => ! empty($session->meeting_room_name),
            ]);

            // If meeting exists, regenerate it with new time
            if (! empty($session->meeting_room_name)) {
                try {
                    // Clear old meeting data first
                    $session->meeting_room_name = null;
                    $session->meeting_link = null;
                    $session->meeting_id = null;
                    $session->meeting_data = null;
                    $session->meeting_expires_at = null;

                    // Generate new meeting with updated time
                    $session->generateMeetingLink();

                    // Save without triggering observer again
                    $session->saveQuietly();

                    Log::info('Meeting regenerated successfully for rescheduled session', [
                        'session_id' => $session->id,
                        'new_room' => $session->meeting_room_name,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to regenerate meeting for rescheduled session', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Log status changes for debugging
        if ($session->wasChanged('status')) {
            $oldStatus = $session->getOriginal('status');
            $newStatus = $session->status;

            Log::info('Session status changed', [
                'session_id' => $session->id,
                'session_type' => $session->getMeetingType(),
                'old_status' => is_string($oldStatus) ? $oldStatus : $oldStatus->value,
                'new_status' => is_string($newStatus) ? $newStatus : $newStatus->value,
            ]);

            // Trigger earnings calculation when session becomes completed
            $newStatusEnum = is_string($newStatus) ? SessionStatus::from($newStatus) : $newStatus;

            if ($newStatusEnum === SessionStatus::COMPLETED) {
                try {
                    Log::info('Dispatching earnings calculation job', [
                        'session_id' => $session->id,
                    ]);

                    dispatch(new CalculateSessionEarningsJob($session));
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch earnings calculation job', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle the BaseSession "created" event.
     */
    public function created(BaseSession $session): void
    {
        // If session is created with ready/ongoing status, create meeting immediately
        $status = is_string($session->status)
            ? SessionStatus::from($session->status)
            : $session->status;

        if (in_array($status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            if (empty($session->meeting_room_name)) {
                try {
                    Log::info('Auto-creating meeting for newly created ready/ongoing session', [
                        'session_id' => $session->id,
                        'status' => $status->value,
                    ]);

                    $session->generateMeetingLink();
                    $session->save();

                    Log::info('Meeting created for new session', [
                        'session_id' => $session->id,
                        'room_name' => $session->meeting_room_name,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create meeting for new session', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
