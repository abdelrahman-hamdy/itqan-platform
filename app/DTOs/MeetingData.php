<?php

namespace App\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for Meeting/Video Conference Data
 *
 * Represents meeting information for LiveKit or other video providers,
 * including room details, tokens, and participant information.
 */
class MeetingData
{
    public function __construct(
        public readonly string $roomName,
        public readonly ?string $roomSid = null,
        public readonly ?string $token = null,
        public readonly ?string $serverUrl = null,
        public readonly ?string $joinUrl = null,
        public readonly string $status = 'created',
        public readonly ?Carbon $createdAt = null,
        public readonly ?Carbon $startedAt = null,
        public readonly ?Carbon $endedAt = null,
        public readonly int $participantCount = 0,
        public readonly int $maxParticipants = 0,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create from LiveKit room data
     */
    public static function fromLiveKitRoom(array $roomData, ?string $token = null): self
    {
        return new self(
            roomName: $roomData['name'] ?? $roomData['room_name'] ?? '',
            roomSid: $roomData['sid'] ?? $roomData['room_sid'] ?? null,
            token: $token,
            serverUrl: config('livekit.server_url'),
            status: $roomData['status'] ?? 'created',
            createdAt: isset($roomData['creation_time'])
                ? Carbon::createFromTimestamp($roomData['creation_time'])
                : null,
            participantCount: $roomData['num_participants'] ?? 0,
            maxParticipants: $roomData['max_participants'] ?? 0,
            metadata: $roomData['metadata'] ?? [],
        );
    }

    /**
     * Create for a new meeting
     */
    public static function forNewMeeting(
        string $roomName,
        string $token,
        string $serverUrl,
        array $metadata = []
    ): self {
        return new self(
            roomName: $roomName,
            token: $token,
            serverUrl: $serverUrl,
            status: 'created',
            createdAt: Carbon::now(),
            metadata: $metadata,
        );
    }

    /**
     * Check if meeting is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'live', 'in_progress']);
    }

    /**
     * Check if meeting has ended
     */
    public function hasEnded(): bool
    {
        return in_array($this->status, ['ended', 'closed', 'completed']);
    }

    /**
     * Check if meeting can be joined
     */
    public function canJoin(): bool
    {
        return ! $this->hasEnded()
            && ! empty($this->token)
            && ! empty($this->serverUrl);
    }

    /**
     * Get meeting duration in minutes
     */
    public function getDurationMinutes(): ?int
    {
        if (! $this->startedAt) {
            return null;
        }

        $endTime = $this->endedAt ?? Carbon::now();

        return (int) $this->startedAt->diffInMinutes($endTime);
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'room_name' => $this->roomName,
            'room_sid' => $this->roomSid,
            'token' => $this->token,
            'server_url' => $this->serverUrl,
            'join_url' => $this->joinUrl,
            'status' => $this->status,
            'created_at' => $this->createdAt?->toDateTimeString(),
            'started_at' => $this->startedAt?->toDateTimeString(),
            'ended_at' => $this->endedAt?->toDateTimeString(),
            'participant_count' => $this->participantCount,
            'max_participants' => $this->maxParticipants,
            'can_join' => $this->canJoin(),
            'is_active' => $this->isActive(),
            'duration_minutes' => $this->getDurationMinutes(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to client-safe array (without sensitive data)
     */
    public function toClientArray(): array
    {
        return [
            'room_name' => $this->roomName,
            'token' => $this->token,
            'server_url' => $this->serverUrl,
            'status' => $this->status,
            'can_join' => $this->canJoin(),
            'is_active' => $this->isActive(),
            'participant_count' => $this->participantCount,
        ];
    }
}
