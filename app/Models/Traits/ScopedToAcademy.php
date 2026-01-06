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

            // Only apply academy scoping if a specific academy is selected
            // If "All Academies" is selected (null), don't apply scoping
            if ($currentAcademyId) {
                $builder->where('academy_id', $currentAcademyId);
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
