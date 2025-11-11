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
            // If "All Academies" is selected (null) or in global view mode, don't apply scoping
            if ($currentAcademyId && !$academyContextService->isGlobalViewMode()) {
                $relationshipPath = static::getAcademyRelationshipPath();
                
                // Handle nested relationships (e.g., 'user.academy')
                if (str_contains($relationshipPath, '.')) {
                    $relationships = explode('.', $relationshipPath);
                    // For paths like 'user.academy', we filter by user.academy_id
                    if (end($relationships) === 'academy') {
                        array_pop($relationships); // Remove 'academy' part
                        $userPath = implode('.', $relationships); // e.g., 'user'
                        
                        $builder->whereHas($userPath, function ($query) use ($currentAcademyId) {
                            $query->where('academy_id', $currentAcademyId);
                        });
                    } else {
                        // For other nested paths, use the final relationship
                        $finalRelationship = array_pop($relationships);
                        $nestedPath = implode('.', $relationships);
                        
                        $builder->whereHas($nestedPath, function ($query) use ($currentAcademyId) {
                            $query->where('academy_id', $currentAcademyId);
                        });
                    }
                } else {
                    // Direct relationship - handle both existing relationships and NULL foreign keys
                    $builder->where(function ($query) use ($relationshipPath, $currentAcademyId) {
                        // Include records with valid academy relationships
                        $query->whereHas($relationshipPath, function ($subQuery) use ($currentAcademyId) {
                            $subQuery->where('academy_id', $currentAcademyId);
                        });
                        
                        // Also include records where the relationship key is NULL but user belongs to academy
                        // This handles orphaned records that should still be accessible
                        if ($relationshipPath === 'gradeLevel') {
                            $query->orWhere(function ($nullQuery) use ($currentAcademyId) {
                                $nullQuery->whereNull('grade_level_id')
                                         ->whereHas('user', function ($userQuery) use ($currentAcademyId) {
                                             $userQuery->where('academy_id', $currentAcademyId);
                                         });
                            });
                        }
                    });
                }
            }
        });
    }
    
    /**
     * Get the relationship path to the academy
     * This method should be overridden in child classes if needed
     */
    protected static function getAcademyRelationshipPath(): string
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