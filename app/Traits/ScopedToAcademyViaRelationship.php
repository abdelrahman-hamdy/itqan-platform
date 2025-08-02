<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\AcademyContextService;

trait ScopedToAcademyViaRelationship
{
    protected static function bootScopedToAcademyViaRelationship()
    {
        static::addGlobalScope('academy_via_relationship', function (Builder $builder) {
            $academyContextService = app(AcademyContextService::class);
            $currentAcademyId = $academyContextService->getCurrentAcademyId();
            
            // Only apply academy scoping if a specific academy is selected
            // If "All Academies" is selected (null), don't apply scoping
            if ($currentAcademyId) {
                $relationshipName = static::getAcademyRelationshipName();
                $builder->whereHas($relationshipName, function ($query) use ($currentAcademyId) {
                    $query->where('academy_id', $currentAcademyId);
                });
            }
        });
    }
    
    /**
     * Get the relationship name to the academy
     */
    protected static function getAcademyRelationshipName(): string
    {
        return 'academy';
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