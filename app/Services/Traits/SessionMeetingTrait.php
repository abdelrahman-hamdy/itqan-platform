<?php

namespace App\Services\Traits;

use App\Enums\SessionStatus;
use App\Models\AcademySettings;
use App\Models\BaseSession;
use App\Models\MeetingAttendance;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Trait SessionMeetingTrait
 *
 * Provides common meeting management functionality for session services.
 * Eliminates ~250 lines of duplication between SessionMeetingService and AcademicSessionMeetingService.
 *
 * Child classes must implement:
 * - getSessionType(): string - Returns 'quran' or 'academic'
 * - getMaxParticipants(): int - Returns max participants for room
 * - getSessionLabel(): string - Returns Arabic label for messages
 * - getJoinUrl(BaseSession $session): string - Returns the URL to join the session
 * - getCacheKeyPrefix(): string - Returns cache key prefix for persistence
 */
trait SessionMeetingTrait
{
    /**
     * Get session timing information
     * This is the core timing logic shared by all session types.
     */
    public function getSessionTiming(BaseSession $session): array
    {
        if (! $session->scheduled_at) {
            return [
                'is_available' => true,
                'is_scheduled' => false,
                'message' => $this->getSessionLabel().' متاحة في أي وقت',
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
            $minutesUntilJoinable = $now->diffInMinutes($joinableStart);

            return [
                'is_available' => false,
                'is_scheduled' => true,
                'message' => $this->getSessionLabel()." ستكون متاحة خلال {$minutesUntilJoinable} دقيقة",
                'status' => 'too_early',
                'minutes_until_available' => $minutesUntilJoinable,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } elseif ($now->between($joinableStart, $sessionStart)) {
            $minutesUntilStart = $now->diffInMinutes($sessionStart);

            return [
                'is_available' => true,
                'is_scheduled' => true,
                'message' => $this->getSessionLabel()." ستبدأ خلال {$minutesUntilStart} دقيقة",
                'status' => 'pre_session',
                'minutes_until_start' => $minutesUntilStart,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } elseif ($now->between($sessionStart, $sessionEnd)) {
            $minutesRemaining = $now->diffInMinutes($sessionEnd);

            return [
                'is_available' => true,
                'is_scheduled' => true,
                'message' => $this->getSessionLabel()." جارية - باقي {$minutesRemaining} دقيقة",
                'status' => 'active',
                'minutes_remaining' => $minutesRemaining,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } elseif ($now->between($sessionEnd, $roomExpiry)) {
            $minutesSinceEnd = $sessionEnd->diffInMinutes($now);

            return [
                'is_available' => true,
                'is_scheduled' => true,
                'message' => 'انتهت '.$this->getSessionLabel()." منذ {$minutesSinceEnd} دقيقة",
                'status' => 'post_session',
                'minutes_since_end' => $minutesSinceEnd,
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        } else {
            return [
                'is_available' => false,
                'is_scheduled' => true,
                'message' => 'انتهت '.$this->getSessionLabel(),
                'status' => 'expired',
                'scheduled_start' => $sessionStart,
                'scheduled_end' => $sessionEnd,
            ];
        }
    }

    /**
     * Calculate empty timeout for room based on session timing
     */
    protected function calculateEmptyTimeout(BaseSession $session): int
    {
        if ($session->scheduled_at) {
            $sessionEnd = $session->scheduled_at->copy()
                ->addMinutes($session->duration_minutes ?? 60);

            $timezone = AcademyContextService::getTimezone();
            $minutesUntilEnd = Carbon::now($timezone)->diffInMinutes($sessionEnd, false);

            if ($minutesUntilEnd > 0) {
                $bufferMinutes = config('livekit.session_settings.timeout_buffer_minutes', 30);

                return ($minutesUntilEnd + $bufferMinutes) * 60;
            }
        }

        return config('livekit.default_room_settings.empty_timeout', 1800);
    }

    /**
     * Calculate maximum duration for room
     */
    protected function calculateMaxDuration(BaseSession $session): int
    {
        $defaultDuration = config('livekit.session_settings.default_duration_minutes', 60);
        $baseDuration = $session->duration_minutes ?? $defaultDuration;

        $overtimeBuffer = config('livekit.session_settings.overtime_buffer_minutes', 60);

        return ($baseDuration + $overtimeBuffer) * 60;
    }

    /**
     * Get session persistence key for tracking active meetings
     */
    public function getSessionPersistenceKey(BaseSession $session): string
    {
        return $this->getCacheKeyPrefix().":{$session->id}:persistence";
    }

    /**
     * Mark session meeting as persistent (survives teacher disconnect)
     */
    public function markSessionPersistent(BaseSession $session, ?int $durationMinutes = null): void
    {
        $duration = $durationMinutes ?? $session->duration_minutes ?? 60;
        $expirationMinutes = $duration + 30;

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

        Log::info('Marked '.$this->getSessionType().' session as persistent', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
            'expires_in_minutes' => $expirationMinutes,
        ]);
    }

    /**
     * Check if session meeting should persist
     */
    public function shouldSessionPersist(BaseSession $session): bool
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
    public function getSessionPersistenceInfo(BaseSession $session): ?array
    {
        return Cache::get($this->getSessionPersistenceKey($session));
    }

    /**
     * Remove session persistence
     */
    public function removeSessionPersistence(BaseSession $session): void
    {
        Cache::forget($this->getSessionPersistenceKey($session));

        Log::info('Removed '.$this->getSessionType().' session persistence', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
        ]);
    }

    /**
     * Get room activity summary
     */
    public function getRoomActivity(BaseSession $session): array
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
     * Check if student attended the session
     */
    protected function checkStudentAttendance(BaseSession $session): bool
    {
        $meetingAttendance = MeetingAttendance::where('session_id', $session->id)
            ->where('user_id', $session->student_id)
            ->first();

        if (! $meetingAttendance) {
            return false;
        }

        $meetingAttendance->calculateFinalAttendance();

        $minimumMinutes = max(5, ($session->duration_minutes ?? 30) * 0.1);
        $actualMinutes = $meetingAttendance->total_duration_minutes;

        Log::info('Checking '.$this->getSessionType().' student attendance', [
            'session_id' => $session->id,
            'student_id' => $session->student_id,
            'actual_minutes' => $actualMinutes,
            'minimum_required' => $minimumMinutes,
            'attended' => $actualMinutes >= $minimumMinutes,
        ]);

        return $actualMinutes >= $minimumMinutes;
    }

    /**
     * Clean up expired session - common logic
     */
    protected function cleanupExpiredSessionCommon(BaseSession $session): void
    {
        try {
            if ($session->meeting_room_name) {
                $this->livekitService->endMeeting($session->meeting_room_name);
            }

            if ($session->session_type === 'individual') {
                $studentAttended = $this->checkStudentAttendance($session);
                $sessionStatus = $studentAttended ? SessionStatus::COMPLETED : SessionStatus::ABSENT;

                Log::info('Individual '.$this->getSessionType().' session status based on attendance', [
                    'session_id' => $session->id,
                    'student_attended' => $studentAttended,
                    'final_status' => $sessionStatus->value,
                ]);
            } else {
                $sessionStatus = SessionStatus::COMPLETED;
            }

            $timezone = AcademyContextService::getTimezone();
            $session->update([
                'status' => $sessionStatus,
                $this->getEndedAtField() => Carbon::now($timezone),
            ]);

            $this->removeSessionPersistence($session);

            Log::info('Cleaned up expired '.$this->getSessionType().' session', [
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
            ]);

        } catch (\Exception $e) {
            Log::error('Error during '.$this->getSessionType().' session cleanup', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Enhanced processing method for session meetings - common logic
     */
    protected function processSessionMeetingsCommon(): array
    {
        $results = [
            'meetings_created' => 0,
            'meetings_terminated' => 0,
            'status_transitions' => 0,
            'errors' => [],
        ];

        try {
            $createResults = $this->createMeetingsForReadySessions();
            $results['meetings_created'] = $createResults['meetings_created'];
            $results['errors'] = array_merge($results['errors'], $createResults['errors']);

            $terminateResults = $this->terminateExpiredMeetings();
            $results['meetings_terminated'] = $terminateResults['meetings_terminated'];
            $results['errors'] = array_merge($results['errors'], $terminateResults['errors']);

        } catch (\Exception $e) {
            $results['errors'][] = [
                'general' => $e->getMessage(),
            ];

            Log::error('Error in '.$this->getSessionType().' session meeting processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }

    /**
     * Get the field name for ended_at timestamp.
     * Override in child classes if different.
     */
    protected function getEndedAtField(): string
    {
        return 'meeting_ended_at';
    }

    // Abstract methods that must be implemented by using classes
    abstract protected function getSessionType(): string;

    abstract protected function getMaxParticipants(): int;

    abstract protected function getSessionLabel(): string;

    abstract protected function getCacheKeyPrefix(): string;
}
