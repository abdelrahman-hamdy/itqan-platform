<?php

namespace App\Enums;

/**
 * Session Type Enum
 *
 * Defines the type of a session (individual 1-on-1 or group).
 * Used across QuranSession and AcademicSession models.
 */
enum SessionType: string
{
    case INDIVIDUAL = 'individual';
    case GROUP = 'group';
    case CIRCLE = 'circle';
    case TRIAL = 'trial';

    /**
     * Get the localized label for the session type.
     */
    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => __('enums.session_type.individual'),
            self::GROUP => __('enums.session_type.group'),
            self::CIRCLE => __('enums.session_type.circle'),
            self::TRIAL => __('enums.session_type.trial'),
        };
    }

    /**
     * Check if this is a group-like type (group or circle).
     */
    public function isGroup(): bool
    {
        return in_array($this, [self::GROUP, self::CIRCLE], true);
    }

    /**
     * Check if this is an individual (1-on-1) type.
     */
    public function isIndividual(): bool
    {
        return $this === self::INDIVIDUAL;
    }

    /**
     * Check if this is a trial session.
     */
    public function isTrial(): bool
    {
        return $this === self::TRIAL;
    }
}
