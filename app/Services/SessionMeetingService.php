<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\AcademySettings;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SessionMeetingService
{
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
     * Ensure meeting room is created and available for session
     */
    public function ensureMeetingAvailable(QuranSession $session, bool $forceCreate = false): array
    {
        // Check if meeting room should be active based on timing
        $sessionTiming = $this->getSessionTiming($session);

        if (! $forceCreate && ! $sessionTiming['is_available']) {
            throw new \Exception($sessionTiming['message']);
        }

        // Create or get existing meeting room
        if (! $session->meeting_room_name) {
            $session->generateMeetingLink();
        }

        // Verify room exists on LiveKit server
        $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);

        if (! $roomInfo) {
            // Room doesn't exist on server, recreate it
            Log::info('Meeting room not found on server, recreating', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
            ]);

            $session->generateMeetingLink([
                'max_participants' => 50,
                'empty_timeout' => $this->calculateEmptyTimeout($session),
                'max_duration' => $this->calculateMaxDuration($session),
            ]);

            $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);
        }

        return [
            'room_name' => $session->meeting_room_name,
            'room_info' => $roomInfo,
            'session_timing' => $sessionTiming,
            'join_url' => route('student.sessions.show', ['subdomain' => $session->academy->subdomain, 'sessionId' => $session->id]), // Meetings are now inline in session pages
        ];
    }

    /**
     * Get session timing information
     */
    public function getSessionTiming(QuranSession $session): array
    {
        if (! $session->scheduled_at) {
            return [
                'is_available' => true,
                'is_scheduled' => false,
                'message' => 'الجلسة متاحة في أي وقت',
                'status' => 'available',
            ];
        }

        $timezone = AcademyContextService::getTimezone();
        $now = Carbon::now($timezone);
        $sessionStart = $session->scheduled_at;
        $sessionEnd = $sessionStart->copy()->addMinutes($session->duration_minutes ?? 60);

        // Get academy settings for timing configuration
        $academySettings = AcademySettings::where('academy_id', $session->academy_id)->first();
        $preparationMinutes = $academySettings?->default_preparation_minutes ?? 10;
        $endingBufferMinutes = $academySettings?->default_buffer_minutes ?? 5;

        // Allow joining based on academy settings
        $joinableStart = $sessionStart->copy()->subMinutes($preparationMinutes);

        // Keep room available based on academy settings
        $roomExpiry = $sessionEnd->copy()->addMinutes($endingBufferMinutes);

        if ($now->lt($joinableStart)) {
            // Too early to join
            $minutesUntilJoinable = $now->diffInMinutes($joinableStart);

            return [
                'is_available' => false,
                'is_scheduled' => true,
                'message' => "الجلسة ستكون متاحة خلال {$minutesUntilJoinable} دقيقة",
                'status' => 'too_early',
                'minutes_until_available' => $minutesUntilJoinable,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } elseif ($now->between($joinableStart, $sessionStart)) {
            // Pre-session period (15 minutes before)
            $minutesUntilStart = $now->diffInMinutes($sessionStart);

            return [
                'is_available' => true,
                'is_scheduled' => true,
                'message' => "الجلسة ستبدأ خلال {$minutesUntilStart} دقيقة",
                'status' => 'pre_session',
                'minutes_until_start' => $minutesUntilStart,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } elseif ($now->between($sessionStart, $sessionEnd)) {
            // During session
            $minutesRemaining = $now->diffInMinutes($sessionEnd);

            return [
                'is_available' => true,
                'is_scheduled' => true,
                'message' => "الجلسة جارية - باقي {$minutesRemaining} دقيقة",
                'status' => 'active',
                'minutes_remaining' => $minutesRemaining,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } elseif ($now->between($sessionEnd, $roomExpiry)) {
            // Post-session grace period
            $minutesSinceEnd = $sessionEnd->diffInMinutes($now);

            return [
                'is_available' => true,
                'is_scheduled' => true,
                'message' => "انتهت الجلسة منذ {$minutesSinceEnd} دقيقة",
                'status' => 'post_session',
                'minutes_since_end' => $minutesSinceEnd,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } else {
            // Session has expired
            return [
                'is_available' => false,
                'is_scheduled' => true,
                'message' => 'انتهت الجلسة',
                'status' => 'expired',
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        }
    }

    /**
     * Process Quran sessions - create meetings for sessions in READY status
     *
     * NOTE: Status transitions are handled by UnifiedSessionStatusService.
     * This method only handles meeting creation and cleanup.
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

        // Get Quran sessions in READY status without meetings yet
        // Note: UnifiedSessionStatusService handles SCHEDULED -> READY transitions
        $readySessions = QuranSession::where('status', SessionStatus::READY)
            ->whereNull('meeting_room_name') // Sessions without meetings yet
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

        // Note: Status transitions to ONGOING are handled by UnifiedSessionStatusService
        // when participant joins (via LiveKit webhook) or via scheduled command.

        // Clean up expired sessions (sessions that ended 2+ hours ago)
        $defaultCleanupHours = 2;
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
     * Get session persistence key for tracking active meetings
     */
    public function getSessionPersistenceKey(QuranSession $session): string
    {
        return "session_meeting:{$session->id}:persistence";
    }

    /**
     * Mark session meeting as persistent (survives teacher disconnect)
     */
    public function markSessionPersistent(QuranSession $session, ?int $durationMinutes = null): void
    {
        $duration = $durationMinutes ?? $session->duration_minutes ?? 60;
        $expirationMinutes = $duration + 30; // Session duration + 30 minutes grace

        $timezone = AcademyContextService::getTimezone();
        $now = Carbon::now($timezone);

        Cache::put(
            $this->getSessionPersistenceKey($session),
            [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
                'created_at' => $now,
                'expires_at' => $now->copy()->addMinutes($expirationMinutes),
                'scheduled_end' => $session->scheduled_at
                    ? $session->scheduled_at->addMinutes($duration)
                    : $now->copy()->addMinutes($duration),
            ],
            $now->copy()->addMinutes($expirationMinutes)
        );

        Log::info('Marked session as persistent', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
            'expires_in_minutes' => $expirationMinutes,
        ]);
    }

    /**
     * Check if session meeting should persist
     */
    public function shouldSessionPersist(QuranSession $session): bool
    {
        $persistenceData = Cache::get($this->getSessionPersistenceKey($session));

        if (! $persistenceData) {
            return false;
        }

        $timezone = AcademyContextService::getTimezone();
        $expiresAt = Carbon::parse($persistenceData['expires_at']);

        return Carbon::now($timezone)->lt($expiresAt);
    }

    /**
     * Get session persistence information
     */
    public function getSessionPersistenceInfo(QuranSession $session): ?array
    {
        return Cache::get($this->getSessionPersistenceKey($session));
    }

    /**
     * Remove session persistence
     */
    public function removeSessionPersistence(QuranSession $session): void
    {
        Cache::forget($this->getSessionPersistenceKey($session));

        Log::info('Removed session persistence', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
        ]);
    }

    /**
     * Calculate empty timeout for room based on session timing
     */
    private function calculateEmptyTimeout(QuranSession $session): int
    {
        if ($session->scheduled_at) {
            $sessionEnd = $session->scheduled_at->copy()
                ->addMinutes($session->duration_minutes ?? 60);

            $timezone = AcademyContextService::getTimezone();
            $minutesUntilEnd = Carbon::now($timezone)->diffInMinutes($sessionEnd, false);

            if ($minutesUntilEnd > 0) {
                // Keep room alive for session duration + buffer
                $bufferMinutes = config('livekit.session_settings.timeout_buffer_minutes', 30);
                return ($minutesUntilEnd + $bufferMinutes) * 60; // Convert to seconds
            }
        }

        // Default: use empty timeout from config
        return config('livekit.default_room_settings.empty_timeout', 1800);
    }

    /**
     * Calculate maximum duration for room
     */
    private function calculateMaxDuration(QuranSession $session): int
    {
        $defaultDuration = config('livekit.session_settings.default_duration_minutes', 60);
        $baseDuration = $session->duration_minutes ?? $defaultDuration;

        // Add buffer for late starts and overtime
        $overtimeBuffer = config('livekit.session_settings.overtime_buffer_minutes', 60);
        return ($baseDuration + $overtimeBuffer) * 60; // Convert to seconds
    }

    /**
     * Clean up expired session
     */
    private function cleanupExpiredSession(QuranSession $session): void
    {
        try {
            // Try to end the meeting room on LiveKit server
            if ($session->meeting_room_name) {
                $this->livekitService->endMeeting($session->meeting_room_name);
            }

            // FIXED: Check attendance before marking session status for individual sessions
            if ($session->session_type === 'individual') {
                // For individual sessions, check if student attended
                $studentAttended = $this->checkStudentAttendance($session);
                $sessionStatus = $studentAttended ? SessionStatus::COMPLETED : SessionStatus::ABSENT;

                Log::info('Individual session status based on attendance', [
                    'session_id' => $session->id,
                    'student_attended' => $studentAttended,
                    'final_status' => $sessionStatus->value,
                ]);
            } else {
                // For group sessions, always mark as completed
                $sessionStatus = SessionStatus::COMPLETED;
            }

            // Update session status
            $timezone = AcademyContextService::getTimezone();
            $session->update([
                'status' => $sessionStatus,
                'meeting_ended_at' => Carbon::now($timezone),
            ]);

            // Remove persistence
            $this->removeSessionPersistence($session);

            Log::info('Cleaned up expired session', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
            ]);

        } catch (\Exception $e) {
            Log::error('Error during session cleanup', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Force create meeting for session (for testing purposes)
     */
    public function forceCreateMeeting(QuranSession $session): array
    {
        return $this->ensureMeetingAvailable($session, true);
    }

    /**
     * Get room activity summary
     */
    public function getRoomActivity(QuranSession $session): array
    {
        if (! $session->meeting_room_name) {
            return [
                'exists' => false,
                'participants' => 0,
                'is_active' => false,
            ];
        }

        $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);

        if (! $roomInfo) {
            return [
                'exists' => false,
                'participants' => 0,
                'is_active' => false,
            ];
        }

        return [
            'exists' => true,
            'participants' => $roomInfo['participant_count'],
            'is_active' => $roomInfo['is_active'],
            'room_info' => $roomInfo,
        ];
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

        // Get sessions that are already READY but don't have meetings yet
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
                // Session is already READY, just create the meeting
                Log::info('Creating meeting for ready session', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                    'status' => $session->status->value,
                ]);

                $session->generateMeetingLink([
                    'max_participants' => 50,
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
     *
     * NOTE: Status transitions (ONGOING -> COMPLETED) are handled by UnifiedSessionStatusService.
     * This method only terminates the LiveKit rooms for cleanup.
     */
    public function terminateExpiredMeetings(): array
    {
        $results = [
            'meetings_terminated' => 0,
            'sessions_processed' => 0,
            'errors' => [],
        ];

        // Get COMPLETED sessions that still have meeting rooms active
        // Note: UnifiedSessionStatusService handles the ONGOING -> COMPLETED transition
        $completedWithMeetings = QuranSession::whereNotNull('meeting_room_name')
            ->where('status', SessionStatus::COMPLETED)
            ->get();

        foreach ($completedWithMeetings as $session) {
            try {
                // End the meeting room on LiveKit
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
     *
     * NOTE: Status transitions are now handled by UnifiedSessionStatusService
     * via the sessions:update-statuses command. This method only handles
     * meeting creation and cleanup.
     */
    public function processSessionMeetings(): array
    {
        $results = [
            'meetings_created' => 0,
            'meetings_terminated' => 0,
            'status_transitions' => 0,
            'errors' => [],
        ];

        try {
            // Status transitions are now handled by UnifiedSessionStatusService
            // via the sessions:update-statuses command. We don't duplicate that logic here.
            // This separation ensures consistent behavior across all session types.

            // Create meetings for ready Quran sessions
            $createResults = $this->createMeetingsForReadySessions();
            $results['meetings_created'] = $createResults['meetings_created'];
            $results['errors'] = array_merge($results['errors'], $createResults['errors']);

            // Terminate expired meetings
            $terminateResults = $this->terminateExpiredMeetings();
            $results['meetings_terminated'] = $terminateResults['meetings_terminated'];
            $results['errors'] = array_merge($results['errors'], $terminateResults['errors']);

        } catch (\Exception $e) {
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];

            Log::error('Error in Quran session meeting processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
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

    /**
     * Check if student attended the individual session
     */
    private function checkStudentAttendance(QuranSession $session): bool
    {
        // Get meeting attendance data
        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $session->student_id)
            ->first();

        if (! $meetingAttendance) {
            return false; // No attendance record = absent
        }

        // Calculate final attendance to ensure sync is done
        $meetingAttendance->calculateFinalAttendance();

        // Check if student attended for meaningful duration
        $minimumMinutes = max(5, ($session->duration_minutes ?? 30) * 0.1); // At least 10% or 5 minutes
        $actualMinutes = $meetingAttendance->total_duration_minutes;

        Log::info('Checking student attendance', [
            'session_id' => $session->id,
            'student_id' => $session->student_id,
            'actual_minutes' => $actualMinutes,
            'minimum_required' => $minimumMinutes,
            'attended' => $actualMinutes >= $minimumMinutes,
        ]);

        return $actualMinutes >= $minimumMinutes;
    }
}
