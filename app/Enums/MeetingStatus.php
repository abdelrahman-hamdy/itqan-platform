<?php

namespace App\Enums;

/**
 * Meeting Status Enum
 *
 * Defines the lifecycle states for meetings associated with sessions.
 * Meetings transition through these states from creation to completion.
 *
 * @see \App\Models\Traits\HasMeetingData
 * @see \App\Services\SessionMeetingService
 * @see \App\Services\AcademicSessionMeetingService
 */
enum MeetingStatus: string
{
    case NOT_CREATED = 'not_created';  // Meeting not yet created
    case READY = 'ready';               // Meeting created, waiting for participants
    case ACTIVE = 'active';             // Meeting is currently in progress
    case ENDED = 'ended';               // Meeting has ended normally
    case CANCELLED = 'cancelled';       // Meeting was cancelled
    case EXPIRED = 'expired';           // Meeting link has expired

    /**
     * Get the localized label for the status
     */
    public function label(): string
    {
        return __('enums.meeting_status.'.$this->value);
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::NOT_CREATED => 'heroicon-o-video-camera-slash',
            self::READY => 'heroicon-o-video-camera',
            self::ACTIVE => 'heroicon-o-play-circle',
            self::ENDED => 'heroicon-o-stop-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::EXPIRED => 'heroicon-o-clock',
        };
    }

    /**
     * Get the Filament color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::NOT_CREATED => 'gray',
            self::READY => 'info',
            self::ACTIVE => 'success',
            self::ENDED => 'primary',
            self::CANCELLED => 'danger',
            self::EXPIRED => 'warning',
        };
    }

    /**
     * Get the hex color for display
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::NOT_CREATED => '#6B7280',  // gray-500
            self::READY => '#3B82F6',        // blue-500
            self::ACTIVE => '#22c55e',       // green-500
            self::ENDED => '#8B5CF6',        // violet-500
            self::CANCELLED => '#ef4444',    // red-500
            self::EXPIRED => '#f59e0b',      // amber-500
        };
    }

    /**
     * Check if meeting can be joined
     */
    public function canJoin(): bool
    {
        return in_array($this, [self::READY, self::ACTIVE]);
    }

    /**
     * Check if meeting can be ended
     */
    public function canEnd(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if meeting is in a final state
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::ENDED, self::CANCELLED, self::EXPIRED]);
    }

    /**
     * Check if meeting is currently active/ongoing
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }

    /**
     * Derive meeting status from session status
     */
    public static function fromSessionStatus(SessionStatus $sessionStatus, bool $hasRoom = false, bool $isExpired = false): self
    {
        if (! $hasRoom) {
            return self::NOT_CREATED;
        }

        if ($isExpired) {
            return self::EXPIRED;
        }

        return match ($sessionStatus) {
            SessionStatus::ONGOING => self::ACTIVE,
            SessionStatus::COMPLETED, SessionStatus::ABSENT => self::ENDED,
            SessionStatus::CANCELLED => self::CANCELLED,
            default => self::READY,
        };
    }
}
