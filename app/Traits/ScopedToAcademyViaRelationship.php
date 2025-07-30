<?php

namespace App\Traits;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;

trait ScopedToAcademyViaRelationship
{
    /**
     * Get the relationship path to academy_id
     * Must be implemented by using class
     */
    abstract protected static function getAcademyRelationshipPath(): string;

    /**
     * Get the Eloquent query scoped to the current academy context via relationship
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Get current academy context
        $academyId = AcademyContextService::getCurrentAcademyId();
        
        // If no academy context (super admin with no academy selected), return empty result
        if (!$academyId) {
            return $query->whereRaw('1 = 0'); // This returns no results
        }
        
        // Get the relationship path from implementing class
        $relationshipPath = static::getAcademyRelationshipPath();
        
        // Scope to current academy via relationship
        return $query->whereHas($relationshipPath, function ($q) use ($academyId) {
            $q->where('academy_id', $academyId);
        });
    }

    /**
     * Get the academy ID for creating new records via relationship
     */
    protected static function getAcademyIdForCreate(): int
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        
        if (!$academyId) {
            throw new \Exception('No academy context available for creating records. Please select an academy first.');
        }
        
        return $academyId;
    }
} 