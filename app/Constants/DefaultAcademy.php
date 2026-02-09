<?php

namespace App\Constants;

/**
 * Default academy configuration constants.
 *
 * Centralizes the default academy subdomain used throughout the application
 * for multi-tenancy fallback behavior.
 */
final class DefaultAcademy
{
    /**
     * The default academy subdomain used as fallback when no subdomain is resolved.
     * Configurable via DEFAULT_ACADEMY_SUBDOMAIN env variable.
     */
    public static function subdomain(): string
    {
        return config('app.default_academy_subdomain', 'itqan-academy');
    }
}
