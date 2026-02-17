<?php

namespace App\Services;

use Exception;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Services\Traits\SessionMeetingTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Academic session meetings.
 *
 * Uses SessionMeetingTrait for common meeting management logic shared
 * with SessionMeetingService (Quran).
 */
class AcademicSessionMeetingService
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
        return 'academic';
    }

    /**
     * Get max participants for Academic sessions (typically 1-on-1)
     */
    protected function getMaxParticipants(): int
    {
        return 2;
    }

    /**
     * Get Arabic label for messages
     */
    protected function getSessionLabel(): string
    {
        return 'الجلسة الأكاديمية';
    }

    /**
     * Get cache key prefix for persistence
     */
    protected function getCacheKeyPrefix(): string
    {
        return 'academic_session_meeting';
    }

    /**
     * Get the field name for ended_at timestamp
     * Academic sessions use 'ended_at' instead of 'meeting_ended_at'
     */
    protected function getEndedAtField(): string
    {
        return 'ended_at';
    }

    /**
     * Ensure meeting room is created and available for academic session
     */
    public function ensureMeetingAvailable(AcademicSession $session, bool $forceCreate = false): array
    {
        $sessionTiming = $this->getSessionTiming($session);

        if (! $forceCreate && ! $sessionTiming['is_available']) {
            throw new Exception($sessionTiming['message']);
        }

        if (! $session->meeting_room_name) {
            $session->generateMeetingLink();
        }

        $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);

        if (! $roomInfo) {
            Log::info('Academic meeting room not found on server, recreating', [
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
            'join_url' => route('student.academic-sessions.show', [
                'subdomain' => $session->academy->subdomain,
                'sessionId' => $session->id,
            ]),
        ];
    }

    /**
     * Process academic sessions - create meetings for sessions in preparation window
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

        $readySessions = AcademicSession::where('status', SessionStatus::READY)
            ->whereNull('meeting_room_name')
            ->with(['academy'])
            ->get();

        foreach ($readySessions as $session) {
            try {
                $this->ensureMeetingAvailable($session, true);
                $results['started']++;

                Log::info('Auto-created meeting for ready academic session', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                    'room_name' => $session->meeting_room_name,
                ]);

            } catch (Exception $e) {
                $results['errors']++;
                Log::error('Failed to auto-create meeting for academic session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $defaultCleanupHours = config('business.meetings.cleanup_after_hours', 2);
        $expiredSessions = AcademicSession::whereNotNull('meeting_room_name')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $now->copy()->subHours($defaultCleanupHours))
            ->where('status', '!=', SessionStatus::COMPLETED)
            ->get();

        foreach ($expiredSessions as $session) {
            try {
                $this->cleanupExpiredSession($session);
                $results['cleaned']++;
            } catch (Exception $e) {
                Log::error('Failed to cleanup expired academic session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Clean up expired academic session
     */
    private function cleanupExpiredSession(AcademicSession $session): void
    {
        $this->cleanupExpiredSessionCommon($session);
    }

    /**
     * Force create meeting for academic session (for testing purposes)
     */
    public function forceCreateMeeting(AcademicSession $session): array
    {
        return $this->ensureMeetingAvailable($session, true);
    }

    /**
     * Create meetings for academic sessions that are ready (based on preparation time)
     */
    public function createMeetingsForReadySessions(): array
    {
        $results = [
            'meetings_created' => 0,
            'sessions_processed' => 0,
            'errors' => [],
        ];

        $readySessions = AcademicSession::where('status', SessionStatus::READY)
            ->whereNull('meeting_room_name')
            ->with(['academy', 'academicSubscription'])
            ->get();

        Log::info('Found ready academic sessions without meetings', [
            'count' => $readySessions->count(),
            'session_ids' => $readySessions->pluck('id')->toArray(),
        ]);

        foreach ($readySessions as $session) {
            try {
                Log::info('Creating meeting for ready academic session', [
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

                Log::info('Meeting created for ready academic session', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);

                $results['sessions_processed']++;

            } catch (Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to create meeting for ready academic session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Terminate meetings for expired academic sessions
     */
    public function terminateExpiredMeetings(): array
    {
        $results = [
            'meetings_terminated' => 0,
            'sessions_processed' => 0,
            'errors' => [],
        ];

        $completedWithMeetings = AcademicSession::whereNotNull('meeting_room_name')
            ->where('status', SessionStatus::COMPLETED)
            ->get();

        foreach ($completedWithMeetings as $session) {
            try {
                if ($session->meeting_room_name) {
                    $this->livekitService->endMeeting($session->meeting_room_name);
                    $results['meetings_terminated']++;
                }

                $results['sessions_processed']++;

                Log::info('Meeting terminated for completed academic session', [
                    'session_id' => $session->id,
                    'status' => $session->status->value,
                ]);

            } catch (Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to terminate meeting for completed academic session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Enhanced processing method for academic session meetings
     */
    public function processSessionMeetings(): array
    {
        return $this->processSessionMeetingsCommon();
    }
}
