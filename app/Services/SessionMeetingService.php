<?php

namespace App\Services;

use App\Models\QuranSession;
use App\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SessionMeetingService
{
    private LiveKitService $livekitService;

    public function __construct(LiveKitService $livekitService)
    {
        $this->livekitService = $livekitService;
    }

    /**
     * Ensure meeting room is created and available for session
     */
    public function ensureMeetingAvailable(QuranSession $session, bool $forceCreate = false): array
    {
        // Check if meeting room should be active based on timing
        $sessionTiming = $this->getSessionTiming($session);
        
        if (!$forceCreate && !$sessionTiming['is_available']) {
            throw new \Exception($sessionTiming['message']);
        }

        // Create or get existing meeting room
        if (!$session->meeting_room_name) {
            $session->generateMeetingLink();
        }

        // Verify room exists on LiveKit server
        $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);
        
        if (!$roomInfo) {
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
        if (!$session->scheduled_at) {
            return [
                'is_available' => true,
                'is_scheduled' => false,
                'message' => 'الجلسة متاحة في أي وقت',
                'status' => 'available',
            ];
        }

        $now = Carbon::now();
        $sessionStart = $session->scheduled_at;
        $sessionEnd = $sessionStart->copy()->addMinutes($session->duration_minutes ?? 60);
        
        // Allow joining 15 minutes before start
        $joinableStart = $sessionStart->copy()->subMinutes(15);
        
        // Keep room available for 30 minutes after session ends for late joiners
        $roomExpiry = $sessionEnd->copy()->addMinutes(30);

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
     * Auto-start sessions that are scheduled to begin
     */
    public function processScheduledSessions(): array
    {
        $results = [
            'started' => 0,
            'updated' => 0,
            'cleaned' => 0,
            'errors' => 0,
        ];

        // Get sessions that should be starting within the next 15 minutes
        $upcomingSessions = QuranSession::where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addMinutes(15))
            ->where('status', '!=', SessionStatus::COMPLETED)
            ->whereNull('meeting_room_name') // Sessions without meetings yet
            ->with(['academy'])
            ->get();

        foreach ($upcomingSessions as $session) {
            try {
                $this->ensureMeetingAvailable($session, true);
                $results['started']++;
                
                Log::info('Auto-created meeting for upcoming session', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                    'room_name' => $session->meeting_room_name,
                ]);
                
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Failed to auto-create meeting for session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update status of active sessions
        $activeSessions = QuranSession::where('scheduled_at', '<=', now())
            ->where('scheduled_at', '>', now()->subMinutes(60))
            ->where('status', SessionStatus::SCHEDULED)
            ->get();

        foreach ($activeSessions as $session) {
            try {
                $session->update(['status' => SessionStatus::ONGOING]);
                $results['updated']++;
            } catch (\Exception $e) {
                Log::error('Failed to update session status', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up expired sessions
        $expiredSessions = QuranSession::whereNotNull('meeting_room_name')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now()->subHours(2)) // Sessions that ended 2+ hours ago
            ->where('status', '!=', SessionStatus::COMPLETED)
            ->get();

        foreach ($expiredSessions as $session) {
            try {
                $this->cleanupExpiredSession($session);
                $results['cleaned']++;
            } catch (\Exception $e) {
                Log::error('Failed to cleanup expired session', [
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
    public function markSessionPersistent(QuranSession $session, int $durationMinutes = null): void
    {
        $duration = $durationMinutes ?? $session->duration_minutes ?? 60;
        $expirationMinutes = $duration + 30; // Session duration + 30 minutes grace
        
        Cache::put(
            $this->getSessionPersistenceKey($session),
            [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
                'created_at' => now(),
                'expires_at' => now()->addMinutes($expirationMinutes),
                'scheduled_end' => $session->scheduled_at 
                    ? $session->scheduled_at->addMinutes($duration)
                    : now()->addMinutes($duration),
            ],
            now()->addMinutes($expirationMinutes)
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
        
        if (!$persistenceData) {
            return false;
        }

        $expiresAt = Carbon::parse($persistenceData['expires_at']);
        return now()->lt($expiresAt);
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
            
            $minutesUntilEnd = now()->diffInMinutes($sessionEnd, false);
            
            if ($minutesUntilEnd > 0) {
                // Keep room alive for session duration + 30 minutes
                return ($minutesUntilEnd + 30) * 60; // Convert to seconds
            }
        }
        
        // Default: 30 minutes empty timeout
        return 30 * 60;
    }

    /**
     * Calculate maximum duration for room
     */
    private function calculateMaxDuration(QuranSession $session): int
    {
        $baseDuration = $session->duration_minutes ?? 60;
        
        // Add 1 hour buffer for late starts and overtime
        return ($baseDuration + 60) * 60; // Convert to seconds
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
            
            // Update session status
            $session->update([
                'status' => SessionStatus::COMPLETED,
                'meeting_ended_at' => now(),
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
        if (!$session->meeting_room_name) {
            return [
                'exists' => false,
                'participants' => 0,
                'is_active' => false,
            ];
        }

        $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);
        
        if (!$roomInfo) {
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
}
