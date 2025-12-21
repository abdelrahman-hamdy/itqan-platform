<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class AcademyContextService
{
    const SELECTED_ACADEMY_SESSION_KEY = 'selected_academy_id';
    const ACADEMY_OBJECT_SESSION_KEY = 'selected_academy';
    const GLOBAL_VIEW_SESSION_KEY = 'super_admin_global_view';

    /**
     * Get the current academy context for the authenticated user
     */
    public static function getCurrentAcademy(): ?Academy
    {
        $user = auth()->user();
        
        if (!$user) {
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
                $academy = Academy::find($academyId);
                if ($academy && $academy->is_active && !$academy->maintenance_mode) {
                    // Cache academy object in session to avoid repeated queries
                    Session::put(self::ACADEMY_OBJECT_SESSION_KEY, $academy);
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
        if ($user->academy && $user->academy->is_active && !$user->academy->maintenance_mode) {
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
        if (app()->bound('current_academy_id')) {
            return app('current_academy_id');
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
        
        if (!$user || !self::isSuperAdmin($user)) {
            return false;
        }

        if ($academyId) {
            $academy = Academy::find($academyId);
            if (!$academy) {
                return false;
            }

            Session::put(self::SELECTED_ACADEMY_SESSION_KEY, $academyId);
            Session::put(self::ACADEMY_OBJECT_SESSION_KEY, $academy);
            
            // Disable global view when selecting a specific academy
            Session::forget(self::GLOBAL_VIEW_SESSION_KEY);
            
            // Make academy available globally for this request
            app()->instance('current_academy', $academy);
        } else {
            // Clear academy context
            Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
            Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
            app()->forgetInstance('current_academy');
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
        return Academy::orderBy('name')
            ->get();
    }

    /**
     * Clear academy context
     */
    public static function clearAcademyContext(): void
    {
        Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
        Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
        app()->forgetInstance('current_academy');
    }

    /**
     * Get the default academy (itqan-academy or first active academy)
     */
    public static function getDefaultAcademy(): ?Academy
    {
        try {
            // First try to get the designated default academy
            $defaultAcademy = Academy::where('subdomain', 'itqan-academy')
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
        return $academy !== null && $academy->is_active && !$academy->maintenance_mode;
    }

    /**
     * Force academy context initialization for Super Admin
     */
    public static function initializeSuperAdminContext(): bool
    {
        if (!self::isSuperAdmin()) {
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
        if (!self::isSuperAdmin()) {
            return false;
        }

        Session::put(self::GLOBAL_VIEW_SESSION_KEY, true);
        
        // Clear academy context when enabling global view
        Session::forget(self::SELECTED_ACADEMY_SESSION_KEY);
        Session::forget(self::ACADEMY_OBJECT_SESSION_KEY);
        app()->forgetInstance('current_academy');
        
        return true;
    }

    /**
     * Disable global view mode for super admin
     */
    public static function disableGlobalView(): bool
    {
        if (!self::isSuperAdmin()) {
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