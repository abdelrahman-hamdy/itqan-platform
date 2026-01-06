<?php

namespace App\Enums;

/**
 * Meeting Event Type Enum
 *
 * Defines the types of participant events that can occur during a meeting.
 * Used for tracking attendance cycles in MeetingAttendance records.
 *
 * @see \App\Models\MeetingAttendance
 * @see \App\Services\Webhook\LiveKit\ParticipantJoinedHandler
 * @see \App\Services\Webhook\LiveKit\ParticipantLeftHandler
 */
enum MeetingEventType: string
{
    case JOINED = 'joined';
    case LEFT = 'left';

    /**
     * Get the localized label for the event type
     */
    public function label(): string
    {
        return __('enums.meeting_event_type.'.$this->value);
    }

    /**
     * Get the icon for the event type
     */
    public function icon(): string
    {
        return match ($this) {
            self::JOINED => 'heroicon-o-arrow-right-on-rectangle',
            self::LEFT => 'heroicon-o-arrow-left-on-rectangle',
        };
    }

    /**
     * Get the color for the event type
     */
    public function color(): string
    {
        return match ($this) {
            self::JOINED => 'success',
            self::LEFT => 'warning',
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for forms
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($type) => $type->label(), self::cases())
        );
    }
}
