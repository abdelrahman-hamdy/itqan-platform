<?php

namespace App\Jobs;

use App\Models\MeetingAttendanceEvent;
use App\Services\LiveKitService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile orphaned attendance events (safety net for missed webhooks)
 *
 * This job closes attendance events that are still open after a reasonable duration,
 * indicating that the participant_left webhook was likely missed.
 */
class ReconcileOrphanedAttendanceEvents implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(LiveKitService $livekitService): void
    {
        Log::info('Starting reconciliation of orphaned attendance events');

        $closedCount = 0;
        $skippedCount = 0;
        $totalFound = 0;
        $chunkSize = 100;

        // Find and process open join events older than 2 hours using chunking
        MeetingAttendanceEvent::where('event_type', 'join')
            ->whereNull('left_at')
            ->where('event_timestamp', '<', now()->subHours(2))
            ->chunk($chunkSize, function ($events) use (&$closedCount, &$skippedCount, &$totalFound, $livekitService) {
                $totalFound += $events->count();

                foreach ($events as $event) {
                    try {
                        // Check if participant is still in the room (via LiveKit API)
                        $isStillInRoom = $this->checkIfParticipantInRoom($event, $livekitService);

                        if ($isStillInRoom) {
                            Log::info('Participant still in room, skipping reconciliation', [
                                'event_id' => $event->id,
                                'participant_sid' => $event->participant_sid,
                                'duration_hours' => $event->event_timestamp->diffInHours(now()),
                            ]);
                            $skippedCount++;
                            continue;
                        }

                        // Close the event with estimated leave time (event timestamp + 2 hours as fallback)
                        $estimatedLeaveTime = $event->event_timestamp->copy()->addHours(2);
                        $durationMinutes = $event->event_timestamp->diffInMinutes($estimatedLeaveTime);

                        $event->update([
                            'left_at' => $estimatedLeaveTime,
                            'duration_minutes' => $durationMinutes,
                            'termination_reason' => 'reconciled_missed_webhook',
                        ]);

                        // Clear attendance status cache
                        \Cache::forget("attendance_status_{$event->session_id}_{$event->user_id}");

                        Log::info('Closed orphaned attendance event', [
                            'event_id' => $event->id,
                            'user_id' => $event->user_id,
                            'session_id' => $event->session_id,
                            'duration_minutes' => $durationMinutes,
                        ]);

                        $closedCount++;
                    } catch (\Exception $e) {
                        Log::error('Error reconciling attendance event', [
                            'event_id' => $event->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Reconciliation complete', [
            'orphaned_events_found' => $totalFound,
            'events_closed' => $closedCount,
            'events_skipped' => $skippedCount,
        ]);
    }

    /**
     * Check if participant is still in the LiveKit room
     */
    private function checkIfParticipantInRoom(MeetingAttendanceEvent $event, LiveKitService $livekitService): bool
    {
        try {
            // Get session to find room name
            $session = $event->session;
            if (! $session) {
                Log::warning('Session not found for attendance event', [
                    'event_id' => $event->id,
                    'session_id' => $event->session_id,
                ]);
                return false;
            }

            // Get meeting to find room name
            $meeting = $session->meetings()->latest()->first();
            if (! $meeting || ! $meeting->livekit_room_name) {
                return false;
            }

            // Use LiveKit service to check participant status
            $participants = $livekitService->listParticipants($meeting->livekit_room_name);

            // Check if our participant is still in the room
            foreach ($participants as $participant) {
                if ($participant['sid'] === $event->participant_sid) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('Error checking if participant in room', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
            ]);
            // If we can't verify, assume they left (safer to close the event)
            return false;
        }
    }
}
