<?php

namespace App\Services\Webhook\LiveKit;

use App\Enums\MeetingEventType;
use App\Models\BaseSession;
use App\Models\MeetingAttendanceEvent;
use App\Models\User;
use App\Services\MeetingAttendanceService;
use Carbon\Carbon;

/**
 * Handler for LiveKit participant_left webhook events.
 *
 * When a participant leaves a meeting, this handler:
 * - Records the leave event and calculates duration
 * - Updates the participant count on the session
 * - Updates attendance records with actual time spent
 */
class ParticipantLeftHandler extends AbstractLiveKitEventHandler
{
    protected string $eventType = 'participant_left';

    public function __construct(
        private readonly MeetingAttendanceService $attendanceService
    ) {}

    public function handle(array $data): void
    {
        $room = $data['room'] ?? [];
        $participant = $data['participant'] ?? [];

        $roomName = $room['name'] ?? null;
        $identity = $participant['identity'] ?? null;

        if (!$roomName || !$identity) {
            $this->logWarning('Participant left event missing required data', [
                'room_name' => $roomName,
                'identity' => $identity,
            ]);
            return;
        }

        $this->logInfo('Processing participant left', [
            'room_name' => $roomName,
            'identity' => $identity,
            'participant_sid' => $participant['sid'] ?? null,
        ]);

        $session = $this->findSessionByRoomName($roomName);

        if (!$session) {
            $this->logWarning('Session not found for room', ['room_name' => $roomName]);
            return;
        }

        $userId = $this->extractUserIdFromIdentity($identity);
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $this->logWarning('User not found for identity', [
                'identity' => $identity,
                'extracted_user_id' => $userId,
            ]);
            return;
        }

        $leftAt = now();
        $this->recordLeaveEvent($session, $user, $participant, $leftAt);
        $this->closeJoinEvent($session, $user, $leftAt, $participant);
        $this->updateParticipantCount($session, $room);
        $this->updateAttendanceRecord($session, $user, $leftAt);
    }

    /**
     * Record the leave event.
     */
    private function recordLeaveEvent(
        BaseSession $session,
        User $user,
        array $participant,
        Carbon $leftAt
    ): void {
        MeetingAttendanceEvent::create([
            'session_type' => get_class($session),
            'session_id' => $session->id,
            'user_id' => $user->id,
            'event_type' => MeetingEventType::LEFT->value,
            'event_id' => $participant['sid'] ?? uniqid('leave_'),
            'participant_identity' => $participant['identity'] ?? null,
            'participant_sid' => $participant['sid'] ?? null,
            'occurred_at' => $leftAt,
            'metadata' => [
                'room_sid' => $session->meeting_room_sid,
                'left_at' => $leftAt->toIso8601String(),
            ],
        ]);

        $this->logInfo('Leave event recorded', [
            'session_id' => $session->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Close the corresponding join event and calculate duration.
     */
    private function closeJoinEvent(
        BaseSession $session,
        User $user,
        Carbon $leftAt,
        array $participant
    ): void {
        // Find the most recent unclosed join event for this user
        $joinEvent = MeetingAttendanceEvent::where('session_type', get_class($session))
            ->where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('event_type', MeetingEventType::JOINED->value)
            ->whereNull('closed_at')
            ->orderBy('occurred_at', 'desc')
            ->first();

        if ($joinEvent) {
            $joinedAt = Carbon::parse($joinEvent->occurred_at);
            $durationMinutes = $joinedAt->diffInMinutes($leftAt);

            $joinEvent->update([
                'closed_at' => $leftAt,
                'duration_minutes' => $durationMinutes,
                'closed_by_event_id' => $participant['sid'] ?? null,
            ]);

            $this->logInfo('Join event closed', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'duration_minutes' => $durationMinutes,
            ]);
        }
    }

    /**
     * Update the participant count on the session.
     */
    private function updateParticipantCount(BaseSession $session, array $room): void
    {
        $participantCount = max(0, ($room['num_participants'] ?? 1) - 1);

        $session->update([
            'participants_count' => $participantCount,
        ]);
    }

    /**
     * Update attendance record with leave time and total duration.
     */
    private function updateAttendanceRecord(BaseSession $session, User $user, Carbon $leftAt): void
    {
        try {
            $this->attendanceService->recordAttendance(
                $session,
                $user,
                MeetingEventType::LEFT,
                $leftAt
            );
        } catch (\Exception $e) {
            $this->logError('Failed to update attendance record', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
