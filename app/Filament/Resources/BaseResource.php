<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use App\Services\AcademyContextService;
use App\Models\Academy;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseResource extends Resource
{
    /**
     * Determine if this resource should be visible in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        // If this is a settings resource, only show when specific academy is selected
        if (static::isSettingsResource()) {
            return static::hasSpecificAcademySelected();
        }
        
        // Data resources are always visible
        return true;
    }
    
    /**
     * Check if specific academy is selected (not "All Academies")
     */
    protected static function hasSpecificAcademySelected(): bool
    {
        $academyContextService = app(AcademyContextService::class);
        return $academyContextService->getCurrentAcademyId() !== null;
    }
    
    /**
     * Check if currently viewing all academies
     */
    protected static function isViewingAllAcademies(): bool
    {
        $academyContextService = app(AcademyContextService::class);
        return $academyContextService->getCurrentAcademyId() === null;
    }
    
    /**
     * Determine if this resource is a settings resource
     * Override in child classes to return true for settings resources
     */
    protected static function isSettingsResource(): bool
    {
        return false;
    }
    
    /**
     * Get academy column for tables when viewing all academies
     */
    protected static function getAcademyColumn(): TextColumn
    {
        // Get the academy relationship path for this resource
        $academyPath = static::getAcademyRelationshipPath();
        
        return TextColumn::make($academyPath . '.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(static::isViewingAllAcademies())
            ->placeholder('غير محدد');
    }
    
    /**
     * Get the Eloquent query with academy relationship eager loaded when needed
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // When viewing all academies, eager load the academy relationship to prevent N+1 queries
        if (static::isViewingAllAcademies()) {
            $academyPath = static::getAcademyRelationshipPath();
            $query->with($academyPath);
        }
        
        return $query;
    }
    
    /**
     * Get the relationship path to academy
     * Override in child classes if academy is not directly related
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }
    
    /**
     * Check if resource can be created when viewing all academies
     */
    public static function canCreate(): bool
    {
        // Don't allow creation when viewing all academies
        if (static::isViewingAllAcademies()) {
            return false;
        }
        
        return parent::canCreate();
    }
    
    /**
     * Get the Academy options for forms
     */
    protected static function getAcademyOptions(): array
    {
        if (static::isViewingAllAcademies()) {
            return Academy::pluck('name', 'id')->toArray();
        }
        
        $academyContextService = app(AcademyContextService::class);
        $currentAcademyId = $academyContextService->getCurrentAcademyId();
        
        if ($currentAcademyId) {
            $academy = Academy::find($currentAcademyId);
            return $academy ? [$academy->id => $academy->name] : [];
        }
        
        return [];
    }
}