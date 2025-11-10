<?php

namespace App\Models;

use App\Services\LiveKitService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;

/**
 * Unified Meeting Model
 *
 * Centralizes all meeting functionality across different session types
 * using polymorphic relationships.
 */
class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'meetable_type',
        'meetable_id',
        'academy_id',
        'livekit_room_name',
        'livekit_room_id',
        'status',
        'scheduled_start_at',
        'actual_start_at',
        'actual_end_at',
        'recording_enabled',
        'recording_url',
        'participant_count',
        'metadata',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'recording_enabled' => 'boolean',
        'participant_count' => 'integer',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'scheduled',
        'recording_enabled' => false,
        'participant_count' => 0,
    ];

    // ========================================
    // Relationships
    // ========================================

    /**
     * Get the owning meetable model (QuranSession, AcademicSession, etc.)
     */
    public function meetable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the academy this meeting belongs to
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get all participants in this meeting
     */
    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    /**
     * Get current active participants (joined but not left)
     */
    public function activeParticipants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class)
            ->whereNotNull('joined_at')
            ->whereNull('left_at');
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_start_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_start_at', '>', now())
            ->where('status', 'scheduled');
    }

    // ========================================
    // Status Management
    // ========================================

    /**
     * Check if meeting is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if meeting has ended
     */
    public function hasEnded(): bool
    {
        return $this->status === 'ended';
    }

    /**
     * Check if meeting is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Start the meeting
     */
    public function start(): bool
    {
        if (!$this->isScheduled()) {
            Log::warning('Cannot start meeting - not in scheduled status', [
                'meeting_id' => $this->id,
                'status' => $this->status,
            ]);
            return false;
        }

        $this->update([
            'status' => 'active',
            'actual_start_at' => now(),
        ]);

        Log::info('Meeting started', [
            'meeting_id' => $this->id,
            'room_name' => $this->livekit_room_name,
        ]);

        return true;
    }

    /**
     * End the meeting
     */
    public function end(): bool
    {
        if (!$this->isActive()) {
            Log::warning('Cannot end meeting - not active', [
                'meeting_id' => $this->id,
                'status' => $this->status,
            ]);
            return false;
        }

        // End LiveKit room
        $livekitService = app(LiveKitService::class);
        $livekitService->endMeeting($this->livekit_room_name);

        // Mark all active participants as left
        $this->activeParticipants()->update([
            'left_at' => now(),
        ]);

        $this->update([
            'status' => 'ended',
            'actual_end_at' => now(),
        ]);

        Log::info('Meeting ended', [
            'meeting_id' => $this->id,
            'duration_minutes' => $this->getActualDurationMinutes(),
        ]);

        return true;
    }

    /**
     * Cancel the meeting
     */
    public function cancel(): bool
    {
        if ($this->hasEnded()) {
            return false;
        }

        $this->update(['status' => 'cancelled']);

        Log::info('Meeting cancelled', [
            'meeting_id' => $this->id,
        ]);

        return true;
    }

    // ========================================
    // Participant Management
    // ========================================

    /**
     * Track user joining the meeting
     */
    public function trackParticipantJoin(User $user, bool $isHost = false): MeetingParticipant
    {
        // Auto-start meeting if not started
        if ($this->isScheduled()) {
            $this->start();
        }

        $participant = $this->participants()->create([
            'user_id' => $user->id,
            'joined_at' => now(),
            'is_host' => $isHost,
        ]);

        // Increment participant count
        $this->increment('participant_count');

        Log::info('Participant joined meeting', [
            'meeting_id' => $this->id,
            'user_id' => $user->id,
            'is_host' => $isHost,
        ]);

        return $participant;
    }

    /**
     * Track user leaving the meeting
     */
    public function trackParticipantLeave(User $user): bool
    {
        $participant = $this->activeParticipants()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$participant) {
            Log::warning('No active participant found to track leave', [
                'meeting_id' => $this->id,
                'user_id' => $user->id,
            ]);
            return false;
        }

        $leftAt = now();
        $durationSeconds = $participant->joined_at->diffInSeconds($leftAt);

        $participant->update([
            'left_at' => $leftAt,
            'duration_seconds' => $durationSeconds,
        ]);

        // Decrement participant count
        if ($this->participant_count > 0) {
            $this->decrement('participant_count');
        }

        Log::info('Participant left meeting', [
            'meeting_id' => $this->id,
            'user_id' => $user->id,
            'duration_seconds' => $durationSeconds,
        ]);

        return true;
    }

    /**
     * Check if user is currently in the meeting
     */
    public function hasParticipant(User $user): bool
    {
        return $this->activeParticipants()
            ->where('user_id', $user->id)
            ->exists();
    }

    // ========================================
    // LiveKit Integration
    // ========================================

    /**
     * Generate access token for a user to join this meeting
     */
    public function generateAccessToken(User $user, array $permissions = []): string
    {
        $livekitService = app(LiveKitService::class);

        return $livekitService->generateParticipantToken(
            $this->livekit_room_name,
            $user,
            $permissions
        );
    }

    /**
     * Get current room information from LiveKit
     */
    public function getRoomInfo(): ?array
    {
        $livekitService = app(LiveKitService::class);

        return $livekitService->getRoomInfo($this->livekit_room_name);
    }

    /**
     * Sync participant count from LiveKit
     */
    public function syncParticipantCount(): int
    {
        $roomInfo = $this->getRoomInfo();

        if ($roomInfo) {
            $count = $roomInfo['participant_count'] ?? 0;
            $this->update(['participant_count' => $count]);
            return $count;
        }

        return 0;
    }

    // ========================================
    // Accessors & Helpers
    // ========================================

    /**
     * Get the actual duration of the meeting in minutes
     */
    public function getActualDurationMinutes(): ?int
    {
        if (!$this->actual_start_at) {
            return null;
        }

        $endTime = $this->actual_end_at ?? now();

        return $this->actual_start_at->diffInMinutes($endTime);
    }

    /**
     * Get the scheduled duration based on session
     */
    public function getScheduledDurationMinutes(): int
    {
        if ($this->meetable && method_exists($this->meetable, 'getMeetingDurationMinutes')) {
            return $this->meetable->getMeetingDurationMinutes();
        }

        return 60; // Default 60 minutes
    }

    /**
     * Get meeting URL for frontend
     */
    public function getMeetingUrl(): string
    {
        return config('app.url') . "/meeting/" . $this->livekit_room_name;
    }

    // ========================================
    // Static Factory Methods
    // ========================================

    /**
     * Create a meeting for a session
     */
    public static function createForSession(
        $session,
        Academy $academy,
        array $options = []
    ): self {
        $livekitService = app(LiveKitService::class);

        // Determine session type
        $sessionType = 'unknown';
        if ($session instanceof QuranSession) {
            $sessionType = 'quran';
        } elseif ($session instanceof AcademicSession) {
            $sessionType = 'academic';
        } elseif ($session instanceof InteractiveCourseSession) {
            $sessionType = 'interactive';
        }

        // Generate room name
        $roomName = static::generateRoomName($academy, $sessionType, $session->id);

        // Create LiveKit room
        $meetingInfo = $livekitService->createMeeting(
            $academy,
            $sessionType,
            $session->id,
            $session->scheduled_at ?? now(),
            $options
        );

        // Create Meeting record
        return static::create([
            'meetable_type' => get_class($session),
            'meetable_id' => $session->id,
            'academy_id' => $academy->id,
            'livekit_room_name' => $meetingInfo['room_name'],
            'livekit_room_id' => $meetingInfo['room_sid'] ?? null,
            'scheduled_start_at' => $session->scheduled_at ?? now(),
            'recording_enabled' => $options['recording_enabled'] ?? false,
            'metadata' => $meetingInfo,
        ]);
    }

    /**
     * Generate a deterministic room name
     */
    protected static function generateRoomName(Academy $academy, string $sessionType, int $sessionId): string
    {
        $academySlug = \Str::slug($academy->subdomain);
        $sessionSlug = \Str::slug($sessionType);

        return "{$academySlug}-{$sessionSlug}-session-{$sessionId}";
    }
}
