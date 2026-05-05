<?php

namespace App\Listeners;

use App\Contracts\RecordingCapable;
use App\Events\SessionCompletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Stop the active recording when a session transitions to completed.
 *
 * Primary end-of-recording trigger. The recordings:stop-expired cron remains
 * a safety net for sessions whose status pipeline failed to fire this event.
 *
 * Idempotent: stopRecording() returns false when there is no active recording,
 * so retries and double-dispatches are harmless.
 */
class StopRecordingOnSessionCompleted implements ShouldQueue
{
    public function handle(SessionCompletedEvent $event): void
    {
        $session = $event->getSession();

        if (! $session instanceof RecordingCapable || ! $session->isRecordingEnabled()) {
            return;
        }

        try {
            $stopped = $session->stopRecording();

            if ($stopped) {
                Log::info('StopRecordingOnSessionCompleted: recording stopped', [
                    'session_id' => $session->id,
                    'session_type' => $event->getSessionType(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('StopRecordingOnSessionCompleted: stopRecording threw', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(SessionCompletedEvent $event, Throwable $exception): void
    {
        Log::error('StopRecordingOnSessionCompleted: job failed', [
            'session_id' => $event->getSession()->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
