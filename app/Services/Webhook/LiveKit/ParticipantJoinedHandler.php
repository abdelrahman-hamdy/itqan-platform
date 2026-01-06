<?php

namespace App\Services\Webhook\LiveKit;

use App\Enums\MeetingEventType;
use App\Models\BaseSession;
use App\Models\MeetingAttendanceEvent;
use App\Models\User;
use App\Services\MeetingAttendanceService;

/**
 * Handler for LiveKit participant_joined webhook events.
 *
 * When a participant joins a meeting, this handler:
 * - Records the join event for attendance tracking
 * - Updates the participant count on the session
 * - Initializes attendance records for reporting
 */
class ParticipantJoinedHandler extends AbstractLiveKitEventHandler
{
    protected string $eventType = 'participant_joined';

    public function __construct(
        private readonly MeetingAttendanceService $attendanceService
    ) {}

    public function handle(array $data): void
    {
        $room = $data['room'] ?? [];
        $participant = $data['participant'] ?? [];

        $roomName = $room['name'] ?? null;
        $identity = $participant['identity'] ?? null;

        if (! $roomName || ! $identity) {
            $this->logWarning('Participant joined event missing required data', [
                'room_name' => $roomName,
                'identity' => $identity,
            ]);

            return;
        }

        $this->logInfo('Processing participant joined', [
            'room_name' => $roomName,
            'identity' => $identity,
            'participant_sid' => $participant['sid'] ?? null,
        ]);

        $session = $this->findSessionByRoomName($roomName);

        if (! $session) {
            $this->logWarning('Session not found for room', ['room_name' => $roomName]);

            return;
        }

        $userId = $this->extractUserIdFromIdentity($identity);
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            $this->logWarning('User not found for identity', [
                'identity' => $identity,
                'extracted_user_id' => $userId,
            ]);

            return;
        }

        $this->recordJoinEvent($session, $user, $participant);
        $this->updateParticipantCount($session, $room);
        $this->initializeAttendance($session, $user);
    }

    /**
     * Record the join event for attendance tracking.
     */
    private function recordJoinEvent(BaseSession $session, User $user, array $participant): void
    {
        // Create or update join event
        MeetingAttendanceEvent::updateOrCreate(
            [
                'session_type' => get_class($session),
                'session_id' => $session->id,
                'user_id' => $user->id,
                'event_type' => MeetingEventType::JOINED->value,
                'event_id' => $participant['sid'] ?? uniqid('join_'),
            ],
            [
                'participant_identity' => $participant['identity'] ?? null,
                'participant_sid' => $participant['sid'] ?? null,
                'occurred_at' => now(),
                'metadata' => [
                    'room_sid' => $session->meeting_room_sid,
                    'joined_at' => now()->toIso8601String(),
                ],
            ]
        );

        $this->logInfo('Join event recorded', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'participant_sid' => $participant['sid'] ?? null,
        ]);
    }

    /**
     * Update the participant count on the session.
     */
    private function updateParticipantCount(BaseSession $session, array $room): void
    {
        $participantCount = $room['num_participants'] ?? 1;

        $session->update([
            'participants_count' => $participantCount,
        ]);
    }

    /**
     * Initialize attendance record for the user.
     */
    private function initializeAttendance(BaseSession $session, User $user): void
    {
        try {
            $this->attendanceService->recordAttendance(
                $session,
                $user,
                MeetingEventType::JOINED,
                now()
            );
        } catch (\Exception $e) {
            $this->logError('Failed to initialize attendance', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - we've already recorded the join event
            report($e);
        }
    }
}
