<?php

namespace App\Jobs;

use App\Contracts\AttendanceEventServiceInterface;
use App\Enums\MeetingEventType;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendanceEvent;
use App\Models\QuranSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Handles delayed processing of leave events when they arrive before join events.
 *
 * This job is dispatched when a participant_left webhook arrives before the
 * corresponding participant_joined webhook (rare race condition).
 * It waits and retries to find the matching join event.
 */
class ProcessDelayedLeaveEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Exponential backoff — gives time for the join webhook to arrive before retrying.
     *
     * @var array<int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $sessionId,
        public string $sessionType,
        public int $userId,
        public string $participantSid,
        public string $leftAt,
        public string $eventId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AttendanceEventServiceInterface $eventService): void
    {
        // Validate sessionType against the known allowlist before any model lookup.
        $allowedSessionTypes = [
            QuranSession::class,
            AcademicSession::class,
            InteractiveCourseSession::class,
        ];

        if (! in_array($this->sessionType, $allowedSessionTypes, true)) {
            Log::warning('[ProcessDelayedLeaveEvent] Invalid session type — aborting', [
                'session_id' => $this->sessionId,
                'session_type' => $this->sessionType,
            ]);

            return;
        }

        Log::info('[ProcessDelayedLeaveEvent] Processing delayed leave event', [
            'session_id' => $this->sessionId,
            'session_type' => $this->sessionType,
            'user_id' => $this->userId,
            'participant_sid' => $this->participantSid,
            'attempt' => $this->attempts(),
        ]);

        // Get the session
        $session = $this->getSession();
        if (! $session) {
            Log::error('[ProcessDelayedLeaveEvent] Session not found', [
                'session_id' => $this->sessionId,
                'session_type' => $this->sessionType,
            ]);

            return;
        }

        // Try to find the matching join event
        $joinEvent = MeetingAttendanceEvent::where('session_id', $this->sessionId)
            ->where('session_type', $this->sessionType)
            ->where('user_id', $this->userId)
            ->where('participant_sid', $this->participantSid)
            ->where('event_type', MeetingEventType::JOINED)
            ->whereNull('left_at')
            ->latest('event_timestamp')
            ->first();

        if (! $joinEvent) {
            Log::warning('[ProcessDelayedLeaveEvent] Join event still not found', [
                'session_id' => $this->sessionId,
                'user_id' => $this->userId,
                'participant_sid' => $this->participantSid,
                'attempt' => $this->attempts(),
            ]);

            // If we've exhausted retries, log and give up
            if ($this->attempts() >= $this->tries) {
                Log::error('[ProcessDelayedLeaveEvent] Exhausted retries - join event never found', [
                    'session_id' => $this->sessionId,
                    'user_id' => $this->userId,
                    'participant_sid' => $this->participantSid,
                ]);

                return;
            }

            // Release back to queue for retry
            $this->release($this->backoff);

            return;
        }

        // Found the join event - close it
        $leftAt = Carbon::parse($this->leftAt);
        $this->closeJoinEvent($joinEvent, $leftAt);

        // Update MeetingAttendance record
        $user = User::find($this->userId);
        if ($user) {
            $eventService->recordLeave($session, $user, [
                'timestamp' => $leftAt,
                'event_id' => $this->eventId,
                'participant_sid' => $this->participantSid,
                'duration_minutes' => $joinEvent->duration_minutes,
            ]);
        }

        Log::info('[ProcessDelayedLeaveEvent] Successfully processed delayed leave event', [
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'participant_sid' => $this->participantSid,
            'duration_minutes' => $joinEvent->duration_minutes,
        ]);
    }

    /**
     * Get the session model based on session type.
     */
    private function getSession()
    {
        return match ($this->sessionType) {
            QuranSession::class => QuranSession::find($this->sessionId),
            AcademicSession::class => AcademicSession::find($this->sessionId),
            InteractiveCourseSession::class => InteractiveCourseSession::find($this->sessionId),
            default => null,
        };
    }

    /**
     * Close the join event with leave time and calculate duration.
     */
    private function closeJoinEvent(MeetingAttendanceEvent $joinEvent, Carbon $leftAt): void
    {
        $duration = $joinEvent->event_timestamp->diffInMinutes($leftAt);

        $joinEvent->update([
            'left_at' => $leftAt,
            'duration_minutes' => $duration,
            'leave_event_id' => $this->eventId,
        ]);
    }
}
