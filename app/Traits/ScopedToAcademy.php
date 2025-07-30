<?php

namespace App\Traits;

use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;

trait ScopedToAcademy
{
    /**
     * Get the Eloquent query scoped to the current academy context
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
        
        // Scope to current academy
        return $query->where('academy_id', $academyId);
    }

    /**
     * Get the academy ID for creating new records
     */
    protected static function getAcademyIdForCreate(): int
    {
        $academyId = AcademyContextService::getCurrentAcademyId();
        
        if (!$academyId) {
            throw new \Exception('No academy context available for creating records. Please select an academy first.');
        }
        
        return $academyId;
    }

    /**
     * Automatically set academy_id when creating records
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = static::getAcademyIdForCreate();
        return $data;
    }

    /**
     * Prevent academy_id modification during updates (optional)
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove academy_id from update data to prevent accidental changes
        unset($data['academy_id']);
        return $data;
    }
} 