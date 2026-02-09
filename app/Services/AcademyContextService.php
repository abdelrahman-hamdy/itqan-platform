<?php

namespace App\Services;

use App\Constants\DefaultAcademy;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class AcademyContextService
{
    public const SELECTED_ACADEMY_SESSION_KEY = 'selected_academy_id';

    public const ACADEMY_OBJECT_SESSION_KEY = 'selected_academy';

    public const GLOBAL_VIEW_SESSION_KEY = 'super_admin_global_view';

    /**
     * Request-scoped API context (set by middleware)
     */
    private static ?Academy $apiContextAcademy = null;

    private static ?int $apiContextAcademyId = null;

    /**
     * Set API request context (called by middleware)
     */
    public static function setApiContext(Academy $academy): void
    {
        self::$apiContextAcademy = $academy;
        self::$apiContextAcademyId = $academy->id;
    }

    /**
     * Clear API request context (for testing or request cleanup)
     */
    public static function clearApiContext(): void
    {
        self::$apiContextAcademy = null;
        self::$apiContextAcademyId = null;
    }

    /**
     * Check if API context is set
     */
    public static function hasApiContext(): bool
    {
        return self::$apiContextAcademyId !== null;
    }

    /**
     * Get API context academy ID
     */
    public static function getApiContextAcademyId(): ?int
    {
        return self::$apiContextAcademyId;
    }

    /**
     * Get the current academy context for the authenticated user
     */
    public static function getCurrentAcademy(): ?Academy
    {
        $user = auth()->user();

        if (! $user) {
            // If no user is authenticated, return the default academy
            return self::getDefaultAcademy();
        }

        // For super admin, use selected academy from session
        if (self::isSuperAdmin($user)) {
            // If in global view mode, return null to indicate no specific academy
            if (self::isGlobalViewMode()) {
                return null;
            }

            $academyId = Session::get(self::SELECTED_ACADEMY_SESSION_KEY);

            if ($academyId) {
                // Cache academy lookups for 10 minutes to reduce per-request DB queries
                // Cache is invalidated when academy settings change (via AcademyObserver)
                $academy = Cache::remember("academy:{$academyId}", 600, function () use ($academyId) {
                    return Academy::find($academyId);
                });
                if ($academy && $academy->is_active && ! $academy->maintenance_mode) {
                    return $academy;
                }
            }

            // Auto-load default academy if none selected or invalid and not in global mode
            $defaultAcademy = self::getDefaultAcademy();
            if ($defaultAcademy) {
                self::setAcademyContext($defaultAcademy->id);

                return $defaultAcademy;
            }

            // If no default academy exists, return null to force academy selection
            return null;
        }

        // For regular users, use their assigned academy
        if ($user->academy && $user->academy->is_active && ! $user->academy->maintenance_mode) {
            return $user->academy;
        }

        // If user has no academy assigned or academy is inactive, return default as fallback
        return self::getDefaultAcademy();
    }

    /**
     * Get the current academy ID for database queries
     */
    public static function getCurrentAcademyId(): ?int
    {
        // First check if academy was set by API middleware (for API requests)
        if (self::hasApiContext()) {
            return self::$apiContextAcademyId;
        }

        $academy = self::getCurrentAcademy();

        return $academy?->id;
    }

    /**
     * Set the academy context for super admin
     */
    public static function setAcademyContext(?int $academyId): bool
    {
        $user = auth()->user();

        if (! $user || ! self::isSuperAdmin($user)) {
            return false;
        }

        if ($academyId) {
            $academy = Academy::find($academyId);
            if (! $academy) {
                return false;
            }

            // Only store the academy ID, not the object (to avoid stale data when settings change)
            Session::put(self::SELECTED_ACADEMY_SESSION_KEY, $academyId);
            Session::forget(self::ACADEMY_OBJECT_SESSION_KEY); // Clear any cached object

            // Disable global view when selecting a specific academy
            Session::forget(self::GLOBAL_VIEW_SESSION_KEY);

            // Also set API context for consistency in mixed web/API scenarios
            self::setApiContext($academy);
        } else {
            // Clear academy context
            Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
            Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
            self::clearApiContext();
        }

        return true;
    }

    /**
     * Check if user is super admin
     */
    public static function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && $user->isSuperAdmin();
    }

    /**
     * Check if super admin has an academy selected
     */
    public static function hasAcademySelected(): bool
    {
        return self::isSuperAdmin() && Session::has(self::SELECTED_ACADEMY_SESSION_KEY);
    }

    /**
     * Get all academies available for super admin selection
     */
    public static function getAvailableAcademies(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('academies:all', 600, function () {
            return Academy::orderBy('name')->get();
        });
    }

    /**
     * Clear academy context
     */
    public static function clearAcademyContext(): void
    {
        Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
        Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
        self::clearApiContext();
    }

    /**
     * Get the default academy (configured default or first active academy)
     */
    public static function getDefaultAcademy(): ?Academy
    {
        try {
            return Cache::remember('academy:default', 1800, function () {
                // First try to get the designated default academy
                $defaultAcademy = Academy::where('subdomain', DefaultAcademy::subdomain())
                    ->where('is_active', true)
                    ->where('maintenance_mode', false)
                    ->first();

                if ($defaultAcademy) {
                    return $defaultAcademy;
                }

                // If no designated default, get the first active academy
                return Academy::where('is_active', true)
                    ->where('maintenance_mode', false)
                    ->orderBy('created_at')
                    ->first();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle case where database tables don't exist (e.g., during testing)
            return null;
        }
    }

    /**
     * Check if we have a valid academy context
     */
    public static function hasValidAcademyContext(): bool
    {
        $academy = self::getCurrentAcademy();

        return $academy !== null && $academy->is_active && ! $academy->maintenance_mode;
    }

    /**
     * Force academy context initialization for Super Admin
     */
    public static function initializeSuperAdminContext(): bool
    {
        if (! self::isSuperAdmin()) {
            return false;
        }

        // Check if already has valid context
        if (self::hasAcademySelected() && self::hasValidAcademyContext()) {
            return true;
        }

        // Try to load default academy
        $defaultAcademy = self::getDefaultAcademy();
        if ($defaultAcademy) {
            return self::setAcademyContext($defaultAcademy->id);
        }

        return false;
    }

    /**
     * Check if super admin is in global view mode
     */
    public static function isGlobalViewMode(): bool
    {
        return self::isSuperAdmin() && Session::get(self::GLOBAL_VIEW_SESSION_KEY, false);
    }

    /**
     * Enable global view mode for super admin
     */
    public static function enableGlobalView(): bool
    {
        if (! self::isSuperAdmin()) {
            return false;
        }

        Session::put(self::GLOBAL_VIEW_SESSION_KEY, true);

        // Clear academy context when enabling global view
        Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
        Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
        self::clearApiContext();

        return true;
    }

    /**
     * Disable global view mode for super admin
     */
    public static function disableGlobalView(): bool
    {
        if (! self::isSuperAdmin()) {
            return false;
        }

        Session::forget(self::GLOBAL_VIEW_SESSION_KEY);

        return true;
    }

    /**
     * Check if current user can access multi-academy management
     */
    public static function canManageMultipleAcademies(): bool
    {
        return self::isSuperAdmin();
    }

    /**
     * Get the timezone for the current academy
     * Falls back to app config timezone if no academy or timezone not set
     */
    public static function getTimezone(): string
    {
        $academy = self::getCurrentAcademy();

        if ($academy && $academy->timezone) {
            // If timezone is a Timezone enum instance, get its value
            if ($academy->timezone instanceof \App\Enums\Timezone) {
                return $academy->timezone->value;
            }

            // If it's already a string, return it
            return $academy->timezone;
        }

        // Fallback to app config timezone
        return config('app.timezone', 'UTC');
    }

    /**
     * Get current time in academy timezone
     *
     * Use this for display purposes and time comparisons that should
     * be relative to the academy's local time (e.g., "is session ready?")
     *
     * Note: Database storage should still use UTC, Laravel handles conversion
     */
    public static function nowInAcademyTimezone(): \Carbon\Carbon
    {
        return \Carbon\Carbon::now(self::getTimezone());
    }

    /**
     * Parse a datetime string in academy timezone
     *
     * Use this when parsing user input that should be interpreted
     * in the academy's local time zone (e.g., form inputs)
     */
    public static function parseInAcademyTimezone(string $datetime): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($datetime, self::getTimezone());
    }

    /**
     * Convert a UTC datetime to academy timezone for display
     */
    public static function toAcademyTimezone(\Carbon\Carbon $datetime): \Carbon\Carbon
    {
        return $datetime->copy()->setTimezone(self::getTimezone());
    }

    /**
     * Convert academy timezone datetime to UTC for database storage
     *
     * IMPORTANT: Laravel's Eloquent does NOT automatically convert datetime to UTC when saving.
     * It just stores the time value as-is, stripping the timezone information.
     * Always use this method before saving datetime fields to ensure consistent UTC storage.
     *
     * @param  \Carbon\Carbon  $datetime  A Carbon instance (should be in academy timezone)
     * @return \Carbon\Carbon The same moment in time, but represented in UTC
     */
    public static function toUtcForStorage(\Carbon\Carbon $datetime): \Carbon\Carbon
    {
        return $datetime->copy()->utc();
    }

    /**
     * Get academy context info for debugging
     */
    public static function getContextInfo(): array
    {
        $user = auth()->user();
        $currentAcademy = self::getCurrentAcademy();

        return [
            'user_id' => $user?->id,
            'user_type' => $user?->user_type,
            'user_academy_id' => $user?->academy_id,
            'is_super_admin' => self::isSuperAdmin($user),
            'selected_academy_id' => Session::get(self::SELECTED_ACADEMY_SESSION_KEY),
            'current_academy_id' => $currentAcademy?->id,
            'current_academy_name' => $currentAcademy?->name,
            'has_academy_selected' => self::hasAcademySelected(),
            'timezone' => self::getTimezone(),
        ];
    }
}
