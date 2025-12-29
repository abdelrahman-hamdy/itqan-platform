<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for LiveKit meeting data
 *
 * Used by LiveKitService to return structured meeting connection information
 * including room details, access tokens, and participant data.
 *
 * @property-read string $roomName LiveKit room name/identifier
 * @property-read string $token JWT access token for participant
 * @property-read string $serverUrl LiveKit server WebSocket URL
 * @property-read string $participantName Participant display name
 * @property-read string $participantId Participant unique identifier
 * @property-read array $metadata Additional meeting metadata
 */
readonly class MeetingData
{
    public function __construct(
        public string $roomName,
        public string $token,
        public string $serverUrl,
        public string $participantName,
        public string $participantId,
        public array $metadata = [],
    ) {}

    /**
     * Create meeting data for a teacher
     */
    public static function forTeacher(
        string $roomName,
        string $token,
        string $serverUrl,
        string $teacherId,
        string $teacherName,
        array $metadata = []
    ): self {
        return new self(
            roomName: $roomName,
            token: $token,
            serverUrl: $serverUrl,
            participantName: $teacherName,
            participantId: $teacherId,
            metadata: array_merge(['role' => 'teacher'], $metadata),
        );
    }

    /**
     * Create meeting data for a student
     */
    public static function forStudent(
        string $roomName,
        string $token,
        string $serverUrl,
        string $studentId,
        string $studentName,
        array $metadata = []
    ): self {
        return new self(
            roomName: $roomName,
            token: $token,
            serverUrl: $serverUrl,
            participantName: $studentName,
            participantId: $studentId,
            metadata: array_merge(['role' => 'student'], $metadata),
        );
    }

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            roomName: $data['roomName'] ?? $data['room_name'],
            token: $data['token'],
            serverUrl: $data['serverUrl'] ?? $data['server_url'],
            participantName: $data['participantName'] ?? $data['participant_name'],
            participantId: $data['participantId'] ?? $data['participant_id'],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'room_name' => $this->roomName,
            'token' => $this->token,
            'server_url' => $this->serverUrl,
            'participant_name' => $this->participantName,
            'participant_id' => $this->participantId,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to JSON-safe array for frontend
     */
    public function toJson(): array
    {
        return [
            'roomName' => $this->roomName,
            'token' => $this->token,
            'serverUrl' => $this->serverUrl,
            'participantName' => $this->participantName,
            'participantId' => $this->participantId,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get participant role from metadata
     */
    public function getRole(): ?string
    {
        return $this->metadata['role'] ?? null;
    }

    /**
     * Check if participant is a teacher
     */
    public function isTeacher(): bool
    {
        return $this->getRole() === 'teacher';
    }

    /**
     * Check if participant is a student
     */
    public function isStudent(): bool
    {
        return $this->getRole() === 'student';
    }
}
