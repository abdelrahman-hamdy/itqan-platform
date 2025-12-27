<?php

namespace App\Services\Webhook\LiveKit;

use App\Enums\SessionStatus;
use App\Models\BaseSession;
use App\Models\MeetingAttendanceEvent;
use App\Services\MeetingAttendanceService;
use Carbon\Carbon;

/**
 * Handler for LiveKit room_finished webhook events.
 *
 * When a room closes in LiveKit, this handler:
 * - Updates the session status to COMPLETED
 * - Closes any unclosed attendance events
 * - Calculates final attendance statistics
 * - Triggers post-session reports generation
 */
class RoomFinishedHandler extends AbstractLiveKitEventHandler
{
    protected string $eventType = 'room_finished';

    public function __construct(
        private readonly MeetingAttendanceService $attendanceService
    ) {}

    public function handle(array $data): void
    {
        $room = $data['room'] ?? [];
        $roomName = $room['name'] ?? null;

        if (!$roomName) {
            $this->logWarning('Room finished event missing room name');
            return;
        }

        $this->logInfo('Processing room finished', [
            'room_name' => $roomName,
            'room_sid' => $room['sid'] ?? null,
        ]);

        $session = $this->findSessionByRoomName($roomName);

        if (!$session) {
            $this->logWarning('Session not found for room', ['room_name' => $roomName]);
            return;
        }

        $endTime = now();

        $this->closeUnclosedEvents($session, $endTime);
        $this->updateSessionStatus($session, $endTime);
        $this->calculateFinalAttendance($session);
    }

    /**
     * Close any unclosed join events for this session.
     */
    private function closeUnclosedEvents(BaseSession $session, Carbon $endTime): void
    {
        $unclosedEvents = MeetingAttendanceEvent::where('session_type', get_class($session))
            ->where('session_id', $session->id)
            ->where('event_type', 'join')
            ->whereNull('closed_at')
            ->get();

        foreach ($unclosedEvents as $event) {
            $joinedAt = Carbon::parse($event->occurred_at);
            $durationMinutes = $joinedAt->diffInMinutes($endTime);

            $event->update([
                'closed_at' => $endTime,
                'duration_minutes' => $durationMinutes,
                'closed_by_event_id' => 'room_closed',
            ]);

            $this->logInfo('Unclosed join event closed', [
                'event_id' => $event->id,
                'user_id' => $event->user_id,
                'duration_minutes' => $durationMinutes,
            ]);
        }

        if ($unclosedEvents->isNotEmpty()) {
            $this->logInfo('Closed unclosed events', [
                'session_id' => $session->id,
                'count' => $unclosedEvents->count(),
            ]);
        }
    }

    /**
     * Update session status to COMPLETED.
     */
    private function updateSessionStatus(BaseSession $session, Carbon $endTime): void
    {
        // Only update if session is in ONGOING status
        if ($session->status === SessionStatus::ONGOING || $session->status->value === SessionStatus::ONGOING->value) {
            $session->update([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => $endTime,
                'meeting_ended_at' => $endTime,
            ]);

            $this->logInfo('Session status updated to COMPLETED', [
                'session_id' => $session->id,
                'session_type' => $session->getMeetingType(),
            ]);

            // Update subscription usage if applicable
            if (method_exists($session, 'updateSubscriptionUsage')) {
                try {
                    $session->updateSubscriptionUsage();
                } catch (\Exception $e) {
                    $this->logError('Failed to update subscription usage', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                    report($e);
                }
            }
        }
    }

    /**
     * Calculate final attendance statistics for the session.
     */
    private function calculateFinalAttendance(BaseSession $session): void
    {
        try {
            $this->attendanceService->calculateSessionAttendance($session);

            $this->logInfo('Final attendance calculated', [
                'session_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to calculate final attendance', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
