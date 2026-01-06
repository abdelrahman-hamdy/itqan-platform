<?php

namespace App\Models;

use App\Enums\MeetingEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MeetingAttendanceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_type',
        'event_timestamp',
        'session_id',
        'session_type',
        'user_id',
        'academy_id',
        'participant_sid',
        'participant_identity',
        'participant_name',
        'left_at',
        'duration_minutes',
        'leave_event_id',
        'raw_webhook_data',
        'termination_reason',
    ];

    protected $casts = [
        'event_type' => MeetingEventType::class,
        'event_timestamp' => 'datetime',
        'left_at' => 'datetime',
        'raw_webhook_data' => 'array',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the session (polymorphic relationship)
     */
    public function session(): MorphTo
    {
        return $this->morphTo('session', 'session_type', 'session_id');
    }

    /**
     * Get the user who attended
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the academy
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Check if this is an active join (not yet left)
     */
    public function isActive(): bool
    {
        return $this->event_type === MeetingEventType::JOINED && $this->left_at === null;
    }

    /**
     * Get total attendance duration for a user in a session
     */
    public static function getTotalDuration(int $sessionId, string $sessionType, int $userId): int
    {
        return self::where('session_id', $sessionId)
            ->where('session_type', $sessionType)
            ->where('user_id', $userId)
            ->whereNotNull('left_at')
            ->sum('duration_minutes') ?? 0;
    }

    /**
     * Check if user is currently in a session
     */
    public static function isUserInSession(int $sessionId, string $sessionType, int $userId): bool
    {
        return self::where('session_id', $sessionId)
            ->where('session_type', $sessionType)
            ->where('user_id', $userId)
            ->where('event_type', MeetingEventType::JOINED)
            ->whereNull('left_at')
            ->exists();
    }

    /**
     * Get all complete attendance cycles for a user in a session
     */
    public static function getCompleteCycles(int $sessionId, string $sessionType, int $userId)
    {
        return self::where('session_id', $sessionId)
            ->where('session_type', $sessionType)
            ->where('user_id', $userId)
            ->where('event_type', MeetingEventType::JOINED)
            ->whereNotNull('left_at')
            ->orderBy('event_timestamp')
            ->get();
    }

    /**
     * Get current active join event (if user is in session)
     */
    public static function getActiveJoin(int $sessionId, string $sessionType, int $userId): ?self
    {
        return self::where('session_id', $sessionId)
            ->where('session_type', $sessionType)
            ->where('user_id', $userId)
            ->where('event_type', MeetingEventType::JOINED)
            ->whereNull('left_at')
            ->latest('event_timestamp')
            ->first();
    }

    /**
     * Get attendance statistics for a session
     */
    public static function getSessionStats(int $sessionId, string $sessionType): array
    {
        $events = self::where('session_id', $sessionId)
            ->where('session_type', $sessionType)
            ->get();

        $uniqueUsers = $events->pluck('user_id')->unique()->count();
        $totalDuration = $events->whereNotNull('duration_minutes')->sum('duration_minutes');
        $averageDuration = $uniqueUsers > 0 ? round($totalDuration / $uniqueUsers) : 0;
        $currentlyJoined = $events->filter(fn ($e) => $e->event_type === MeetingEventType::JOINED && $e->left_at === null)
            ->count();

        return [
            'total_attendees' => $uniqueUsers,
            'currently_joined' => $currentlyJoined,
            'total_duration_minutes' => $totalDuration,
            'average_duration_minutes' => $averageDuration,
        ];
    }
}
