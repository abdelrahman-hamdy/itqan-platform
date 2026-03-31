<?php

namespace App\Services\Subscription;

use App\Models\AcademicSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSubscription;
use InvalidArgumentException;

/**
 * Resolves subscription model classes from type strings.
 *
 * Eliminates the duplicated match() expression that existed in
 * SubscriptionCreationService, SubscriptionQueryService, and SubscriptionAnalyticsService.
 */
class SubscriptionTypeResolver
{
    public const TYPE_QURAN = 'quran';

    public const TYPE_ACADEMIC = 'academic';

    public const TYPE_COURSE = 'course';

    /**
     * Resolve the Eloquent model class for a subscription type.
     */
    public static function resolveModelClass(string $type): string
    {
        return match ($type) {
            self::TYPE_QURAN => QuranSubscription::class,
            self::TYPE_ACADEMIC => AcademicSubscription::class,
            self::TYPE_COURSE => CourseSubscription::class,
            default => throw new InvalidArgumentException("Unknown subscription type: {$type}"),
        };
    }

    /**
     * Check if a subscription type uses session-based status.
     */
    public static function isSessionBased(string $type): bool
    {
        return in_array($type, [self::TYPE_QURAN, self::TYPE_ACADEMIC]);
    }

    /**
     * Get all valid subscription types.
     */
    public static function validTypes(): array
    {
        return [self::TYPE_QURAN, self::TYPE_ACADEMIC, self::TYPE_COURSE];
    }
}
