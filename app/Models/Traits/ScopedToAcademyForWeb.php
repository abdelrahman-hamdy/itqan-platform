<?php

namespace App\Models\Traits;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait ScopedToAcademyForWeb
 *
 * Similar to ScopedToAcademy, but explicitly skips scoping in console context.
 * Use this for session models that need to be queried across academies by
 * console commands and background jobs.
 *
 * Scoping is applied ONLY when:
 * - Running in web context (not console)
 * - Not in unit tests
 * - Super admin is NOT in global view mode
 * - A specific academy context is set
 */
trait ScopedToAcademyForWeb
{
    protected static function bootScopedToAcademyForWeb(): void
    {
        static::addGlobalScope('academy_web', function (Builder $builder) {
            // Skip in console context (jobs, commands) - they manage their own filtering
            if (app()->runningInConsole() && !app()->runningUnitTests()) {
                return;
            }

            $academyContextService = app(AcademyContextService::class);

            // Skip for super admin in global view mode
            if ($academyContextService->isSuperAdmin() && $academyContextService->isGlobalViewMode()) {
                return;
            }

            $currentAcademyId = $academyContextService->getCurrentAcademyId();

            // Only apply scoping if a specific academy is selected
            if ($currentAcademyId) {
                $builder->where('academy_id', $currentAcademyId);
            }
        });
    }

    /**
     * Check if currently in console context (jobs, commands)
     */
    public static function isRunningInConsole(): bool
    {
        return app()->runningInConsole() && !app()->runningUnitTests();
    }

    /**
     * Check if currently viewing all academies (super admin global view)
     */
    public static function isViewingAllAcademies(): bool
    {
        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->isSuperAdmin()
            && $academyContextService->isGlobalViewMode();
    }

    /**
     * Check if scoping is currently active
     */
    public static function isScopingActive(): bool
    {
        // Not active in console
        if (static::isRunningInConsole()) {
            return false;
        }

        // Not active in global view
        if (static::isViewingAllAcademies()) {
            return false;
        }

        // Not active if no academy context
        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() !== null;
    }
}
