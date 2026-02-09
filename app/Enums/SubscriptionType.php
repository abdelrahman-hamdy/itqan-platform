<?php

namespace App\Enums;

/**
 * Subscription Type Enum
 *
 * Defines the types of subscriptions available.
 */
enum SubscriptionType: string
{
    case QURAN = 'quran';
    case ACADEMIC = 'academic';
    case COURSE = 'course';

    /**
     * Get the localized label.
     */
    public function label(): string
    {
        return match ($this) {
            self::QURAN => __('enums.subscription_type.quran'),
            self::ACADEMIC => __('enums.subscription_type.academic'),
            self::COURSE => __('enums.subscription_type.course'),
        };
    }

    /**
     * Get all session-based subscription types (excluding courses).
     */
    public static function sessionTypes(): array
    {
        return [self::QURAN, self::ACADEMIC];
    }
}
