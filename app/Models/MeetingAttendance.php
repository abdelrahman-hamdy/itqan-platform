<?php

namespace App\Models;

use App\Models\AcademicSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'user_type',
        'session_type',
        'first_join_time',
        'last_leave_time',
        'total_duration_minutes',
        'join_leave_cycles',
        'attendance_calculated_at',
        'attendance_status',
        'attendance_percentage',
        'session_duration_minutes',
        'session_start_time',
        'session_end_time',
        'join_count',
        'leave_count',
        'is_calculated',
    ];

    protected $casts = [
        'first_join_time' => 'datetime',
        'last_leave_time' => 'datetime',
        'join_leave_cycles' => 'array',
        'attendance_calculated_at' => 'datetime',
        'session_start_time' => 'datetime',
        'session_end_time' => 'datetime',
        'attendance_percentage' => 'decimal:2',
        'total_duration_minutes' => 'integer',
        'session_duration_minutes' => 'integer',
        'join_count' => 'integer',
        'leave_count' => 'integer',
        'is_calculated' => 'boolean',
    ];

    /**
     * Polymorphic relationship with session (QuranSession or AcademicSession)
     */
    public function session(): BelongsTo
    {
        // Check session_type to determine which model to use
        if ($this->session_type === 'academic') {
            return $this->belongsTo(AcademicSession::class, 'session_id');
        }
        
        // Default to QuranSession for backwards compatibility
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    /**
     * Specifically get QuranSession
     */
    public function quranSession(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'session_id');
    }

    /**
     * Specifically get AcademicSession
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Record a user joining the meeting
     */
    public function recordJoin(): bool
    {
        $now = now();
        $cycles = $this->join_leave_cycles ?? [];

        // Check if user is already in the meeting (has joined but not left)
        $lastCycle = end($cycles);
        if ($lastCycle && isset($lastCycle['joined_at']) && ! isset($lastCycle['left_at'])) {
            Log::info('User already in meeting, updating join status', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
                'last_cycle' => $lastCycle,
            ]);

            // Return true since user is already in meeting (this is success)
            return true;
        }

        // Add new join event
        $cycles[] = [
            'joined_at' => $now->toISOString(),
            'left_at' => null,
        ];

        $this->update([
            'first_join_time' => $this->first_join_time ?? $now,
            'join_leave_cycles' => $cycles,
            'join_count' => $this->join_count + 1,
        ]);

        Log::info('User joined meeting', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'join_time' => $now,
            'join_count' => $this->join_count,
            'is_currently_in_meeting' => $this->isCurrentlyInMeeting(),
        ]);

        return true;
    }

    /**
     * Record a user leaving the meeting
     */
    public function recordLeave(): bool
    {
        $now = now();
        $cycles = $this->join_leave_cycles ?? [];

        // Find the last open cycle (joined but not left)
        $lastCycleIndex = null;
        for ($i = count($cycles) - 1; $i >= 0; $i--) {
            if (isset($cycles[$i]['joined_at']) && ! isset($cycles[$i]['left_at'])) {
                $lastCycleIndex = $i;
                break;
            }
        }

        if ($lastCycleIndex === null) {
            Log::warning('User tried to leave meeting but not currently in meeting', [
                'session_id' => $this->session_id,
                'user_id' => $this->user_id,
            ]);

            return false;
        }

        // Update the last cycle with leave time
        $cycles[$lastCycleIndex]['left_at'] = $now->toISOString();

        // Calculate duration for this cycle
        $joinTime = Carbon::parse($cycles[$lastCycleIndex]['joined_at']);
        $cycleDurationMinutes = $joinTime->diffInMinutes($now);
        $cycles[$lastCycleIndex]['duration_minutes'] = $cycleDurationMinutes;

        $this->update([
            'last_leave_time' => $now,
            'join_leave_cycles' => $cycles,
            'leave_count' => $this->leave_count + 1,
            'total_duration_minutes' => $this->calculateTotalDuration($cycles),
        ]);

        Log::info('User left meeting', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'leave_time' => $now,
            'cycle_duration' => $cycleDurationMinutes,
            'total_duration' => $this->total_duration_minutes,
        ]);

        return true;
    }

    /**
     * Calculate total duration from all cycles
     */
    private function calculateTotalDuration(array $cycles): int
    {
        $totalMinutes = 0;

        foreach ($cycles as $cycle) {
            if (isset($cycle['joined_at']) && isset($cycle['left_at'])) {
                $joinTime = Carbon::parse($cycle['joined_at']);
                $leaveTime = Carbon::parse($cycle['left_at']);
                $totalMinutes += $joinTime->diffInMinutes($leaveTime);
            }
        }

        return $totalMinutes;
    }

    /**
     * Calculate final attendance after session ends
     */
    public function calculateFinalAttendance(): bool
    {
        if ($this->is_calculated) {
            return true; // Already calculated
        }

        $session = $this->session;
        if (! $session) {
            return false;
        }

        // Get circle configuration for this session
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;

        if (! $circle) {
            return false;
        }

        $graceMinutes = $circle->late_join_grace_period_minutes ?? 15;
        $sessionDuration = $session->duration_minutes ?? 60;
        $sessionStartTime = $session->scheduled_at;

        // Calculate attendance status
        $attendanceStatus = $this->determineAttendanceStatus(
            $sessionStartTime,
            $sessionDuration,
            $graceMinutes
        );

        // Calculate attendance percentage
        $attendancePercentage = $sessionDuration > 0
            ? ($this->total_duration_minutes / $sessionDuration) * 100
            : 0;

        $this->update([
            'attendance_status' => $attendanceStatus,
            'attendance_percentage' => min(100, $attendancePercentage),
            'session_duration_minutes' => $sessionDuration,
            'session_start_time' => $sessionStartTime,
            'session_end_time' => $sessionStartTime->copy()->addMinutes($sessionDuration),
            'attendance_calculated_at' => now(),
            'is_calculated' => true,
        ]);

        Log::info('Final attendance calculated', [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'attendance_status' => $attendanceStatus,
            'attendance_percentage' => $attendancePercentage,
            'total_duration' => $this->total_duration_minutes,
            'session_duration' => $sessionDuration,
        ]);

        return true;
    }

    /**
     * Determine attendance status based on join time and duration
     */
    private function determineAttendanceStatus(
        Carbon $sessionStartTime,
        int $sessionDuration,
        int $graceMinutes
    ): string {
        // If never joined, definitely absent
        if (! $this->first_join_time) {
            return 'absent';
        }

        $attendancePercentage = $sessionDuration > 0
            ? ($this->total_duration_minutes / $sessionDuration) * 100
            : 0;

        // Determine status based on attendance percentage
        if ($attendancePercentage < 30) {
            return 'absent';
        } elseif ($attendancePercentage < 80) {
            return 'partial';
        }

        // If attended 80%+, check if they were late
        $lateThreshold = $sessionStartTime->copy()->addMinutes($graceMinutes);
        $wasLate = $this->first_join_time->isAfter($lateThreshold);

        return $wasLate ? 'late' : 'present';
    }

    /**
     * Check if user is currently in the meeting
     */
    public function isCurrentlyInMeeting(): bool
    {
        $cycles = $this->join_leave_cycles ?? [];
        $lastCycle = end($cycles);

        return $lastCycle && isset($lastCycle['joined_at']) && ! isset($lastCycle['left_at']);
    }

    /**
     * Get the current session duration if user is still in meeting
     */
    public function getCurrentSessionDuration(): int
    {
        if (! $this->isCurrentlyInMeeting()) {
            return $this->total_duration_minutes;
        }

        $cycles = $this->join_leave_cycles ?? [];
        $lastCycle = end($cycles);

        if (! $lastCycle || ! isset($lastCycle['joined_at'])) {
            return $this->total_duration_minutes;
        }

        $joinTime = Carbon::parse($lastCycle['joined_at']);
        $currentDuration = $joinTime->diffInMinutes(now());

        return $this->total_duration_minutes + $currentDuration;
    }

    /**
     * Scopes
     */
    public function scopePresent($query)
    {
        return $query->where('attendance_status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('attendance_status', 'late');
    }

    public function scopePartial($query)
    {
        return $query->where('attendance_status', 'partial');
    }

    public function scopeCalculated($query)
    {
        return $query->where('is_calculated', true);
    }

    public function scopeNotCalculated($query)
    {
        return $query->where('is_calculated', false);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Static method to find or create meeting attendance (QuranSession - backwards compatibility)
     */
    public static function findOrCreateForUser(QuranSession $session, User $user): self
    {
        return static::firstOrCreate([
            'session_id' => $session->id,
            'user_id' => $user->id,
        ], [
            'user_type' => $user->user_type === 'quran_teacher' ? 'teacher' : 'student',
            'session_type' => $session->session_type ?? 'individual',
            'join_leave_cycles' => [],
            'join_count' => 0,
            'leave_count' => 0,
            'total_duration_minutes' => 0,
            'attendance_status' => 'absent',
            'attendance_percentage' => 0,
            'is_calculated' => false,
        ]);
    }

    /**
     * Static method to find or create meeting attendance (Polymorphic version)
     */
    public static function findOrCreateForUserPolymorphic($session, User $user, string $sessionType): self
    {
        $userType = 'student'; // Default
        
        if ($sessionType === 'academic') {
            $userType = $user->user_type === 'academic_teacher' ? 'teacher' : 'student';
        } else {
            $userType = $user->user_type === 'quran_teacher' ? 'teacher' : 'student';
        }

        return static::firstOrCreate([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'session_type' => $sessionType,
        ], [
            'user_type' => $userType,
            'join_leave_cycles' => [],
            'join_count' => 0,
            'leave_count' => 0,
            'total_duration_minutes' => 0,
            'attendance_status' => 'absent',
            'attendance_percentage' => 0,
            'is_calculated' => false,
        ]);
    }
}
