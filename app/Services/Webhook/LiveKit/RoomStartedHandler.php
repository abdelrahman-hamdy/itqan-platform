<?php

namespace App\Services\Webhook\LiveKit;

use App\Enums\SessionStatus;
use App\Models\BaseSession;
use App\Services\LiveKitService;
use App\Services\MeetingAttendanceService;
use App\Services\RecordingService;

/**
 * Handler for LiveKit room_started webhook events.
 *
 * When a room is created in LiveKit, this handler:
 * - Updates the session status to ONGOING
 * - Records the meeting start time
 * - Optionally starts auto-recording if enabled
 */
class RoomStartedHandler extends AbstractLiveKitEventHandler
{
    protected string $eventType = 'room_started';

    public function __construct(
        private readonly MeetingAttendanceService $attendanceService,
        private readonly RecordingService $recordingService,
        private readonly LiveKitService $liveKitService
    ) {}

    public function handle(array $data): void
    {
        $room = $data['room'] ?? [];
        $roomName = $room['name'] ?? null;

        if (!$roomName) {
            $this->logWarning('Room started event missing room name');
            return;
        }

        $this->logInfo('Processing room started', [
            'room_name' => $roomName,
            'room_sid' => $room['sid'] ?? null,
        ]);

        $session = $this->findSessionByRoomName($roomName);

        if (!$session) {
            $this->logWarning('Session not found for room', ['room_name' => $roomName]);
            return;
        }

        $this->updateSessionStatus($session);
        $this->recordMeetingStart($session, $room);
        $this->tryStartAutoRecording($session, $roomName);
    }

    /**
     * Update session status to ONGOING.
     */
    private function updateSessionStatus(BaseSession $session): void
    {
        if ($session->status === SessionStatus::SCHEDULED || $session->status->value === SessionStatus::SCHEDULED->value) {
            $session->update([
                'status' => SessionStatus::ONGOING,
                'started_at' => now(),
            ]);

            $this->logInfo('Session status updated to ONGOING', [
                'session_id' => $session->id,
                'session_type' => $session->getMeetingType(),
            ]);
        }
    }

    /**
     * Record the meeting room SID for tracking.
     * Note: started_at is already set in updateSessionStatus()
     */
    private function recordMeetingStart(BaseSession $session, array $room): void
    {
        $session->update([
            'meeting_room_sid' => $room['sid'] ?? null,
        ]);
    }

    /**
     * Start auto-recording if enabled for the session.
     */
    private function tryStartAutoRecording(BaseSession $session, string $roomName): void
    {
        // Check if recording is enabled for this session
        $recordingEnabled = $session->recording_enabled ?? false;

        if (!$recordingEnabled) {
            return;
        }

        try {
            $egressId = $this->liveKitService->startRoomRecording($roomName, [
                'session_id' => $session->id,
                'session_type' => $session->getMeetingType(),
            ]);

            if ($egressId) {
                // Store the egress ID for later reference
                $this->recordingService->createRecordingRecord($session, [
                    'egress_id' => $egressId,
                    'room_name' => $roomName,
                    'started_at' => now(),
                    'status' => 'recording',
                ]);

                $this->logInfo('Auto-recording started', [
                    'session_id' => $session->id,
                    'egress_id' => $egressId,
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('Failed to start auto-recording', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - recording failure shouldn't block the session
            report($e);
        }
    }
}
