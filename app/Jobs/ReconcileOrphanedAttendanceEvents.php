<?php

namespace App\Jobs;

use Cache;
use Exception;
use Throwable;
use App\Enums\MeetingEventType;
use App\Jobs\Traits\TenantAwareJob;
use App\Models\Academy;
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
 *
 * MULTI-TENANCY: Processes events grouped by academy for proper tenant isolation.
 */
class ReconcileOrphanedAttendanceEvents implements ShouldQueue
{
    use Queueable, TenantAwareJob;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * The number of seconds to wait before retrying with exponential backoff.
     */
    public array $backoff = [30, 60, 120];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     *
     * MULTI-TENANCY: Processes events grouped by academy for proper tenant isolation.
     */
    public function handle(LiveKitService $livekitService): void
    {
        Log::info('Starting reconciliation of orphaned attendance events (multi-tenant)');

        $totalClosed = 0;
        $totalSkipped = 0;
        $totalFound = 0;

        // Process each academy separately for tenant isolation
        $this->processForEachAcademy(function (Academy $academy) use ($livekitService, &$totalClosed, &$totalSkipped, &$totalFound) {
            $closed = 0;
            $skipped = 0;
            $found = 0;

            $this->processAcademyEvents($academy, $livekitService, $closed, $skipped, $found);

            $totalClosed += $closed;
            $totalSkipped += $skipped;
            $totalFound += $found;

            return [
                'closed' => $closed,
                'skipped' => $skipped,
                'found' => $found,
            ];
        });

        Log::info('Reconciliation complete (multi-tenant)', [
            'orphaned_events_found' => $totalFound,
            'events_closed' => $totalClosed,
            'events_skipped' => $totalSkipped,
        ]);
    }

    /**
     * Process orphaned events for a specific academy.
     */
    private function processAcademyEvents(Academy $academy, LiveKitService $livekitService, int &$closedCount, int &$skippedCount, int &$totalFound): void
    {
        $chunkSize = 100;

        // Default orphan threshold: use the business config default session duration,
        // converted to hours (rounded up), with a minimum of 2 hours as a safety floor.
        $defaultDurationMinutes = config('business.sessions.default_duration_minutes', 60);
        $orphanThresholdMinutes = max($defaultDurationMinutes, 120); // at least 2 hours

        // Find and process open join events older than the orphan threshold using chunking.
        // Filter by academy via session relationship.
        MeetingAttendanceEvent::where('event_type', MeetingEventType::JOINED)
            ->whereNull('left_at')
            ->where('event_timestamp', '<', now()->subMinutes($orphanThresholdMinutes))
            ->whereHas('session', function ($query) use ($academy) {
                $query->where('academy_id', $academy->id);
            })
            ->chunk($chunkSize, function ($events) use (&$closedCount, &$skippedCount, &$totalFound, $livekitService, $academy, $orphanThresholdMinutes) {
                $totalFound += $events->count();

                foreach ($events as $event) {
                    try {
                        // Check if participant is still in the room (via LiveKit API)
                        $isStillInRoom = $this->checkIfParticipantInRoom($event, $livekitService);

                        if ($isStillInRoom) {
                            Log::info('Participant still in room, skipping reconciliation', [
                                'event_id' => $event->id,
                                'participant_sid' => $event->participant_sid,
                                'duration_minutes' => $event->event_timestamp->diffInMinutes(now()),
                                'academy_id' => $academy->id,
                            ]);
                            $skippedCount++;

                            continue;
                        }

                        // Determine per-event threshold: prefer the actual session's
                        // duration_minutes; fall back to the configured default.
                        $session = $event->session;
                        $sessionDurationMinutes = $session?->duration_minutes
                            ?? config('business.sessions.default_duration_minutes', 60);

                        // Estimated leave = join time + session duration (capped to now if in future)
                        $estimatedLeaveTime = $event->event_timestamp->copy()
                            ->addMinutes($sessionDurationMinutes);

                        if ($estimatedLeaveTime->isAfter(now())) {
                            $estimatedLeaveTime = now();
                        }

                        $durationMinutes = $event->event_timestamp->diffInMinutes($estimatedLeaveTime);

                        $event->update([
                            'left_at' => $estimatedLeaveTime,
                            'duration_minutes' => $durationMinutes,
                            'termination_reason' => 'reconciled_missed_webhook',
                        ]);

                        // Clear attendance status cache
                        Cache::forget("attendance_status_{$event->session_id}_{$event->user_id}");

                        Log::info('Closed orphaned attendance event', [
                            'event_id' => $event->id,
                            'user_id' => $event->user_id,
                            'session_id' => $event->session_id,
                            'duration_minutes' => $durationMinutes,
                            'academy_id' => $academy->id,
                        ]);

                        $closedCount++;
                    } catch (Exception $e) {
                        Log::error('Error reconciling attendance event', [
                            'event_id' => $event->id,
                            'error' => $e->getMessage(),
                            'academy_id' => $academy->id,
                        ]);
                    }
                }
            });
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
        } catch (Exception $e) {
            Log::warning('Error checking if participant in room', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
            ]);

            // If we can't verify, assume they left (safer to close the event)
            return false;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ReconcileOrphanedAttendanceEvents job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
