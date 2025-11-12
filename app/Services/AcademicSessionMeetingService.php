<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademySettings;
use App\Models\MeetingAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AcademicSessionMeetingService
{
    private LiveKitService $livekitService;

    private SessionStatusService $sessionStatusService;

    public function __construct(
        LiveKitService $livekitService,
        SessionStatusService $sessionStatusService
    ) {
        $this->livekitService = $livekitService;
        $this->sessionStatusService = $sessionStatusService;
    }

    /**
     * Ensure meeting room is created and available for academic session
     */
    public function ensureMeetingAvailable(AcademicSession $session, bool $forceCreate = false): array
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
            Log::info('Academic meeting room not found on server, recreating', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
            ]);

            $session->generateMeetingLink([
                'max_participants' => 2, // Academic sessions are typically 1-on-1
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
     * Get academic session timing information
     */
    public function getSessionTiming(AcademicSession $session): array
    {
        if (! $session->scheduled_at) {
            return [
                'is_available' => true,
                'is_scheduled' => false,
                'message' => 'الجلسة الأكاديمية متاحة في أي وقت',
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

        // Allow joining based on preparation time
        $joinableStart = $sessionStart->copy()->subMinutes($preparationMinutes);

        // Keep room available with buffer
        $roomExpiry = $sessionEnd->copy()->addMinutes($endingBufferMinutes);

        if ($now->lt($joinableStart)) {
            // Too early to join
            $minutesUntilJoinable = $now->diffInMinutes($joinableStart);

            return [
                'is_available' => false,
                'is_scheduled' => true,
                'message' => "الجلسة الأكاديمية ستكون متاحة خلال {$minutesUntilJoinable} دقيقة",
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
                'message' => "الجلسة الأكاديمية ستبدأ خلال {$minutesUntilStart} دقيقة",
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
                'message' => "الجلسة الأكاديمية جارية - باقي {$minutesRemaining} دقيقة",
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
                'message' => "انتهت الجلسة الأكاديمية منذ {$minutesSinceEnd} دقيقة",
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
                'message' => 'انتهت الجلسة الأكاديمية',
                'status' => 'expired',
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        }
    }

    /**
     * Auto-start academic sessions that are scheduled to begin
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

        // Get academic sessions that should be starting within the preparation window
        $upcomingSessions = AcademicSession::where('scheduled_at', '>=', $now)
            ->where('scheduled_at', '<=', $now->copy()->addMinutes(30)) // Max window to check
            ->where('status', '!=', SessionStatus::COMPLETED)
            ->whereNull('meeting_room_name') // Sessions without meetings yet
            ->with(['academy'])
            ->get()
            ->filter(function ($session) use ($now) {
                // Check if session is within the academy's preparation window
                $academySettings = AcademySettings::where('academy_id', $session->academy_id)->first();
                $preparationMinutes = $academySettings?->default_preparation_minutes ?? 10;
                return $session->scheduled_at <= $now->copy()->addMinutes($preparationMinutes);
            });

        foreach ($upcomingSessions as $session) {
            try {
                $this->ensureMeetingAvailable($session, true);
                $results['started']++;

                Log::info('Auto-created meeting for upcoming academic session', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                    'room_name' => $session->meeting_room_name,
                ]);

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Failed to auto-create meeting for academic session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update status of active academic sessions
        $activeSessions = AcademicSession::where('scheduled_at', '<=', $now)
            ->where('scheduled_at', '>', $now->copy()->subMinutes(60))
            ->where('status', SessionStatus::SCHEDULED)
            ->get();

        foreach ($activeSessions as $session) {
            try {
                $session->update(['status' => SessionStatus::ONGOING]);
                $results['updated']++;
            } catch (\Exception $e) {
                Log::error('Failed to update academic session status', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up expired academic sessions
        $expiredSessions = AcademicSession::whereNotNull('meeting_room_name')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $now->copy()->subHours(2)) // Sessions that ended 2+ hours ago
            ->where('status', '!=', SessionStatus::COMPLETED)
            ->get();

        foreach ($expiredSessions as $session) {
            try {
                $this->cleanupExpiredSession($session);
                $results['cleaned']++;
            } catch (\Exception $e) {
                Log::error('Failed to cleanup expired academic session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get academic session persistence key for tracking active meetings
     */
    public function getSessionPersistenceKey(AcademicSession $session): string
    {
        return "academic_session_meeting:{$session->id}:persistence";
    }

    /**
     * Mark academic session meeting as persistent (survives teacher disconnect)
     */
    public function markSessionPersistent(AcademicSession $session, ?int $durationMinutes = null): void
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

        Log::info('Marked academic session as persistent', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
            'expires_in_minutes' => $expirationMinutes,
        ]);
    }

    /**
     * Check if academic session meeting should persist
     */
    public function shouldSessionPersist(AcademicSession $session): bool
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
     * Get academic session persistence information
     */
    public function getSessionPersistenceInfo(AcademicSession $session): ?array
    {
        return Cache::get($this->getSessionPersistenceKey($session));
    }

    /**
     * Remove academic session persistence
     */
    public function removeSessionPersistence(AcademicSession $session): void
    {
        Cache::forget($this->getSessionPersistenceKey($session));

        Log::info('Removed academic session persistence', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
        ]);
    }

    /**
     * Calculate empty timeout for room based on academic session timing
     */
    private function calculateEmptyTimeout(AcademicSession $session): int
    {
        if ($session->scheduled_at) {
            $sessionEnd = $session->scheduled_at->copy()
                ->addMinutes($session->duration_minutes ?? 60);

            $timezone = AcademyContextService::getTimezone();
            $minutesUntilEnd = Carbon::now($timezone)->diffInMinutes($sessionEnd, false);

            if ($minutesUntilEnd > 0) {
                // Keep room alive for session duration + 30 minutes
                return ($minutesUntilEnd + 30) * 60; // Convert to seconds
            }
        }

        // Default: 30 minutes empty timeout
        return 30 * 60;
    }

    /**
     * Calculate maximum duration for academic session room
     */
    private function calculateMaxDuration(AcademicSession $session): int
    {
        $baseDuration = $session->duration_minutes ?? 60;

        // Add 1 hour buffer for late starts and overtime
        return ($baseDuration + 60) * 60; // Convert to seconds
    }

    /**
     * Clean up expired academic session
     */
    private function cleanupExpiredSession(AcademicSession $session): void
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

                Log::info('Individual academic session status based on attendance', [
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
                'ended_at' => Carbon::now($timezone),
            ]);

            // Remove persistence
            $this->removeSessionPersistence($session);

            Log::info('Cleaned up expired academic session', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
            ]);

        } catch (\Exception $e) {
            Log::error('Error during academic session cleanup', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Force create meeting for academic session (for testing purposes)
     */
    public function forceCreateMeeting(AcademicSession $session): array
    {
        return $this->ensureMeetingAvailable($session, true);
    }

    /**
     * Get room activity summary for academic session
     */
    public function getRoomActivity(AcademicSession $session): array
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
     * Create meetings for academic sessions that are ready (based on preparation time)
     */
    public function createMeetingsForReadySessions(): array
    {
        $results = [
            'meetings_created' => 0,
            'sessions_processed' => 0,
            'errors' => [],
        ];

        // Get academic sessions that are already READY but don't have meetings yet
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
                // Session is already READY, just create the meeting
                Log::info('Creating meeting for ready academic session', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                    'status' => $session->status->value,
                ]);

                $session->generateMeetingLink([
                    'max_participants' => 2, // Academic sessions are 1-on-1
                    'empty_timeout' => $this->calculateEmptyTimeout($session),
                    'max_duration' => $this->calculateMaxDuration($session),
                ]);

                $results['meetings_created']++;

                Log::info('Meeting created for ready academic session', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);

                $results['sessions_processed']++;

            } catch (\Exception $e) {
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

        $timezone = AcademyContextService::getTimezone();
        $now = Carbon::now($timezone);

        // Get academic sessions that should have meetings terminated
        $expiredSessions = AcademicSession::whereNotNull('meeting_room_name')
            ->whereIn('status', [SessionStatus::ONGOING, SessionStatus::READY])
            ->with(['academy', 'academicSubscription'])
            ->get()
            ->filter(function ($session) use ($now) {
                // For academic sessions, check if they should be auto-completed
                // (e.g., if they're more than duration + buffer past their scheduled end)
                if (! $session->scheduled_at) {
                    return false;
                }

                $sessionEnd = $session->scheduled_at->copy()
                    ->addMinutes($session->duration_minutes ?? 60);
                $bufferEnd = $sessionEnd->copy()->addMinutes(30); // 30 minute buffer

                return $now->gt($bufferEnd);
            });

        foreach ($expiredSessions as $session) {
            try {
                // End the meeting
                if ($session->meeting_room_name) {
                    $this->livekitService->endMeeting($session->meeting_room_name);
                    $results['meetings_terminated']++;
                }

                // Transition session to completed
                $timezone = AcademyContextService::getTimezone();
                $session->update([
                    'status' => SessionStatus::COMPLETED,
                    'ended_at' => Carbon::now($timezone),
                ]);
                $results['sessions_processed']++;

                Log::info('Meeting terminated for expired academic session', [
                    'session_id' => $session->id,
                    'status' => $session->status->value,
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to terminate meeting for expired academic session', [
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
        $results = [
            'meetings_created' => 0,
            'meetings_terminated' => 0,
            'status_transitions' => 0,
            'errors' => [],
        ];

        try {
            // CRITICAL FIX: Process status transitions FIRST
            // This ensures sessions transition to READY before we try to create meetings
            $transitionSessions = AcademicSession::whereIn('status', [
                SessionStatus::SCHEDULED,
                SessionStatus::READY,
                SessionStatus::ONGOING,
            ])->with(['academy', 'academicSubscription'])->get();

            // Simple status transitions for academic sessions
            $transitionsCount = 0;
            foreach ($transitionSessions as $session) {
                try {
                    $oldStatus = $session->status;

                    // Check if session should transition to READY
                    if ($session->status === SessionStatus::SCHEDULED && $session->scheduled_at) {
                        $timezone = AcademyContextService::getTimezone();
                        $now = Carbon::now($timezone);
                        $preparationTime = $session->scheduled_at->copy()->subMinutes(15);
                        if ($now->gte($preparationTime)) {
                            $session->update(['status' => SessionStatus::READY]);
                            $transitionsCount++;
                        }
                    }

                    // Check if session should transition to ONGOING
                    if ($session->status === SessionStatus::READY && $session->scheduled_at) {
                        $timezone = AcademyContextService::getTimezone();
                        $now = Carbon::now($timezone);
                        if ($now->gte($session->scheduled_at)) {
                            $session->update(['status' => SessionStatus::ONGOING]);
                            $transitionsCount++;
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to process academic session status transition', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $results['status_transitions'] = $transitionsCount;

            Log::info('Academic status transitions processed', [
                'transitions_count' => $transitionsCount,
            ]);

            // THEN create meetings for ready academic sessions (including newly transitioned ones)
            $createResults = $this->createMeetingsForReadySessions();
            $results['meetings_created'] = $createResults['meetings_created'];
            $results['errors'] = array_merge($results['errors'], $createResults['errors']);

            // Finally, terminate expired meetings
            $terminateResults = $this->terminateExpiredMeetings();
            $results['meetings_terminated'] = $terminateResults['meetings_terminated'];
            $results['errors'] = array_merge($results['errors'], $terminateResults['errors']);

        } catch (\Exception $e) {
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];

            Log::error('Error in academic session meeting processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }

    /**
     * Check if student attended the individual academic session
     */
    private function checkStudentAttendance(AcademicSession $session): bool
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

        Log::info('Checking academic student attendance', [
            'session_id' => $session->id,
            'student_id' => $session->student_id,
            'actual_minutes' => $actualMinutes,
            'minimum_required' => $minimumMinutes,
            'attended' => $actualMinutes >= $minimumMinutes,
        ]);

        return $actualMinutes >= $minimumMinutes;
    }
}
