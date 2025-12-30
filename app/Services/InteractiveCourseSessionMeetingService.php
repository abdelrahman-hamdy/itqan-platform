<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\InteractiveCourseSession;
use App\Services\Traits\SessionMeetingTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Interactive Course session meetings.
 *
 * Uses SessionMeetingTrait for common meeting management logic shared
 * with SessionMeetingService (Quran) and AcademicSessionMeetingService.
 */
class InteractiveCourseSessionMeetingService
{
    use SessionMeetingTrait;

    private LiveKitService $livekitService;

    private UnifiedSessionStatusService $statusService;

    public function __construct(
        LiveKitService $livekitService,
        UnifiedSessionStatusService $statusService
    ) {
        $this->livekitService = $livekitService;
        $this->statusService = $statusService;
    }

    /**
     * Get the session type identifier
     */
    protected function getSessionType(): string
    {
        return 'interactive';
    }

    /**
     * Get max participants for Interactive Course sessions (group courses can have many)
     */
    protected function getMaxParticipants(): int
    {
        return 100;
    }

    /**
     * Get Arabic label for messages
     */
    protected function getSessionLabel(): string
    {
        return 'جلسة الدورة التفاعلية';
    }

    /**
     * Get cache key prefix for persistence
     */
    protected function getCacheKeyPrefix(): string
    {
        return 'interactive_session_meeting';
    }

    /**
     * Get the field name for ended_at timestamp
     */
    protected function getEndedAtField(): string
    {
        return 'ended_at';
    }

    /**
     * Ensure meeting room is created and available for interactive course session
     */
    public function ensureMeetingAvailable(InteractiveCourseSession $session, bool $forceCreate = false): array
    {
        $sessionTiming = $this->getSessionTiming($session);

        if (! $forceCreate && ! $sessionTiming['is_available']) {
            throw new \Exception($sessionTiming['message']);
        }

        if (! $session->meeting_room_name) {
            $session->generateMeetingLink();
        }

        $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);

        if (! $roomInfo) {
            Log::info('Interactive course meeting room not found on server, recreating', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
            ]);

            $session->generateMeetingLink([
                'max_participants' => $this->getMaxParticipants(),
                'empty_timeout' => $this->calculateEmptyTimeout($session),
                'max_duration' => $this->calculateMaxDuration($session),
            ]);

            $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);
        }

        return [
            'room_name' => $session->meeting_room_name,
            'room_info' => $roomInfo,
            'session_timing' => $sessionTiming,
            'meeting_link' => $session->meeting_link,
        ];
    }

    /**
     * Generate a meeting token for a participant
     */
    public function generateParticipantToken(InteractiveCourseSession $session, $user, array $permissions = []): string
    {
        if (! $session->meeting_room_name) {
            throw new \Exception('لا يوجد رابط اجتماع لهذه الجلسة');
        }

        $defaultPermissions = [
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'hidden' => false,
            'recorder' => false,
        ];

        $mergedPermissions = array_merge($defaultPermissions, $permissions);

        return $this->livekitService->generateToken(
            $session->meeting_room_name,
            $user->id,
            $user->name,
            $mergedPermissions
        );
    }

    /**
     * End a meeting room for interactive course session
     */
    public function endMeeting(InteractiveCourseSession $session): bool
    {
        if (! $session->meeting_room_name) {
            return false;
        }

        try {
            // Close the LiveKit room
            $this->livekitService->deleteRoom($session->meeting_room_name);

            // Update session status
            $session->update([
                'status' => SessionStatus::COMPLETED,
                $this->getEndedAtField() => AcademyContextService::nowInAcademyTimezone(),
            ]);

            Log::info('Interactive course meeting ended', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to end interactive course meeting', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get meeting status for interactive course session
     */
    public function getMeetingStatus(InteractiveCourseSession $session): array
    {
        $timing = $this->getSessionTiming($session);

        $roomInfo = null;
        $participantCount = 0;

        if ($session->meeting_room_name) {
            $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);
            $participantCount = $roomInfo['num_participants'] ?? 0;
        }

        return [
            'session_id' => $session->id,
            'session_status' => $session->status->value ?? $session->status,
            'has_meeting' => (bool) $session->meeting_room_name,
            'room_name' => $session->meeting_room_name,
            'timing' => $timing,
            'participant_count' => $participantCount,
            'is_active' => $roomInfo !== null && $participantCount > 0,
        ];
    }
}
