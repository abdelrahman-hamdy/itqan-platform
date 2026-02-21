<?php

namespace App\Models\Traits;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;

trait ScopedToAcademy
{
    protected static function bootScopedToAcademy()
    {
        static::addGlobalScope('academy', function (Builder $builder) {
            $academyContextService = app(AcademyContextService::class);
            $currentAcademyId = $academyContextService->getCurrentAcademyId();

            if ($currentAcademyId) {
                $builder->where('academy_id', $currentAcademyId);
            } elseif (! $academyContextService->isSuperAdmin() || ! $academyContextService->isGlobalViewMode()) {
                // No academy context and not super admin in global view → return empty results
                // Prevents accidental cross-tenant data exposure (MT-001)
                $builder->whereRaw('0 = 1');
            }
        });
    }

    /**
     * Check if currently viewing all academies
     */
    public static function isViewingAllAcademies(): bool
    {
        $academyContextService = app(AcademyContextService::class);

        return $academyContextService->getCurrentAcademyId() === null;
    }
}
