<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\QuranSession;
use App\Services\Traits\SessionMeetingTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Quran session meetings.
 *
 * Uses SessionMeetingTrait for common meeting management logic shared
 * with AcademicSessionMeetingService.
 */
class SessionMeetingService
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
        return 'quran';
    }

    /**
     * Get max participants for Quran sessions (group circles can have many)
     */
    protected function getMaxParticipants(): int
    {
        return 50;
    }

    /**
     * Get Arabic label for messages
     */
    protected function getSessionLabel(): string
    {
        return 'الجلسة';
    }

    /**
     * Get cache key prefix for persistence
     */
    protected function getCacheKeyPrefix(): string
    {
        return 'session_meeting';
    }

    /**
     * Ensure meeting room is created and available for session
     */
    public function ensureMeetingAvailable(QuranSession $session, bool $forceCreate = false): array
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
            Log::info('Meeting room not found on server, recreating', [
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
            'join_url' => route('student.sessions.show', [
                'subdomain' => $session->academy->subdomain,
                'sessionId' => $session->id,
            ]),
        ];
    }

    /**
     * Process Quran sessions - create meetings for sessions in READY status
     */
    public function processScheduledSessions(): array
    {
        $results = [
            'started' => 0,
            'updated' => 0,
            'cleaned' => 0,
            'errors' => 0,
        ];

        $timezone = AcademyContextService::getTimezone();
        $now = Carbon::now($timezone);

        $readySessions = QuranSession::where('status', SessionStatus::READY)
            ->whereNull('meeting_room_name')
            ->with(['academy'])
            ->get();

        foreach ($readySessions as $session) {
            try {
                $this->ensureMeetingAvailable($session, true);
                $results['started']++;

                Log::info('Auto-created meeting for ready Quran session', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                    'room_name' => $session->meeting_room_name,
                ]);

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Failed to auto-create meeting for Quran session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $defaultCleanupHours = config('business.meetings.cleanup_after_hours', 2);
        $expiredSessions = QuranSession::whereNotNull('meeting_room_name')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $now->copy()->subHours($defaultCleanupHours))
            ->where('status', '!=', SessionStatus::COMPLETED)
            ->get();

        foreach ($expiredSessions as $session) {
            try {
                $this->cleanupExpiredSession($session);
                $results['cleaned']++;
            } catch (\Exception $e) {
                Log::error('Failed to cleanup expired Quran session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Clean up expired session
     */
    private function cleanupExpiredSession(QuranSession $session): void
    {
        $this->cleanupExpiredSessionCommon($session);
    }

    /**
     * Force create meeting for session (for testing purposes)
     */
    public function forceCreateMeeting(QuranSession $session): array
    {
        return $this->ensureMeetingAvailable($session, true);
    }

    /**
     * Create meetings for sessions that are ready (based on preparation time)
     */
    public function createMeetingsForReadySessions(): array
    {
        $results = [
            'meetings_created' => 0,
            'sessions_processed' => 0,
            'errors' => [],
        ];

        $readySessions = QuranSession::where('status', SessionStatus::READY)
            ->whereNull('meeting_room_name')
            ->with(['academy', 'circle', 'individualCircle'])
            ->get();

        Log::info('Found ready sessions without meetings', [
            'count' => $readySessions->count(),
            'session_ids' => $readySessions->pluck('id')->toArray(),
        ]);

        foreach ($readySessions as $session) {
            try {
                Log::info('Creating meeting for ready session', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                    'status' => $session->status->value,
                ]);

                $session->generateMeetingLink([
                    'max_participants' => $this->getMaxParticipants(),
                    'empty_timeout' => $this->calculateEmptyTimeout($session),
                    'max_duration' => $this->calculateMaxDuration($session),
                ]);

                $results['meetings_created']++;

                Log::info('Meeting created for ready session', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);

                $results['sessions_processed']++;

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to create meeting for ready session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Terminate meetings for expired Quran sessions
     */
    public function terminateExpiredMeetings(): array
    {
        $results = [
            'meetings_terminated' => 0,
            'sessions_processed' => 0,
            'errors' => [],
        ];

        $completedWithMeetings = QuranSession::whereNotNull('meeting_room_name')
            ->where('status', SessionStatus::COMPLETED)
            ->get();

        foreach ($completedWithMeetings as $session) {
            try {
                if ($session->endMeeting()) {
                    $results['meetings_terminated']++;
                }

                $results['sessions_processed']++;

                Log::info('Meeting terminated for completed Quran session', [
                    'session_id' => $session->id,
                    'status' => $session->status->value,
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to terminate meeting for completed Quran session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Enhanced processing method for Quran session meetings
     */
    public function processSessionMeetings(): array
    {
        return $this->processSessionMeetingsCommon();
    }

    /**
     * Get the appropriate circle (individual or group) for a session
     */
    private function getCircleForSession(QuranSession $session)
    {
        return $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
    }
}
