<?php

namespace App\Enums;

/**
 * Enrollment Type Enum
 *
 * Defines how a student was enrolled in a course subscription.
 */
enum EnrollmentType: string
{
    case FREE = 'free';
    case PAID = 'paid';
    case TRIAL = 'trial';
    case GIFT = 'gift';

    /**
     * Get the localized label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE => __('enums.enrollment_type.free'),
            self::PAID => __('enums.enrollment_type.paid'),
            self::TRIAL => __('enums.enrollment_type.trial'),
            self::GIFT => __('enums.enrollment_type.gift'),
        };
    }
}
