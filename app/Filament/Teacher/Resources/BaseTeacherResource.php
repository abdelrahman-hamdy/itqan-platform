<?php

namespace App\Filament\Teacher\Resources;

use Filament\Resources\Resource;
use App\Filament\Resources\BaseResource as SuperAdminBaseResource;
use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseTeacherResource extends SuperAdminBaseResource
{
    /**
     * Determine if this resource should be visible in navigation
     * Teacher resources are always visible within teacher dashboard
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    /**
     * Check if current user is a teacher
     */
    protected static function isTeacher(): bool
    {
        $user = auth()->user();
        return $user && ($user->isQuranTeacher() || $user->isAcademicTeacher());
    }
    
    /**
     * Get current teacher's academy
     */
    protected static function getCurrentTeacherAcademy(): ?\App\Models\Academy
    {
        $academyContextService = app(AcademyContextService::class);
        return $academyContextService->getCurrentAcademy();
    }
    
    /**
     * Get current teacher's ID
     */
    protected static function getCurrentTeacherId(): ?int
    {
        return auth()->id();
    }
    
    /**
     * Check if record belongs to current teacher
     * Override in child classes for specific ownership logic
     */
    public static function canView(Model $record): bool
    {
        if (!static::isTeacher()) {
            return false;
        }
        
        // Default: allow viewing if record belongs to teacher's academy
        $teacherAcademy = static::getCurrentTeacherAcademy();
        if ($teacherAcademy && $record->academy_id === $teacherAcademy->id) {
            return true;
        }
        
        return parent::canView($record);
    }
    
    /**
     * Check if record can be edited by current teacher
     * Override in child classes for specific edit permissions
     */
    public static function canEdit(Model $record): bool
    {
        if (!static::isTeacher()) {
            return false;
        }
        
        // Default: allow editing if record belongs to teacher's academy
        $teacherAcademy = static::getCurrentTeacherAcademy();
        if ($teacherAcademy && $record->academy_id === $teacherAcademy->id) {
            return true;
        }
        
        return parent::canEdit($record);
    }
    
    /**
     * Get the Eloquent query with teacher-specific filtering
     * Override in child classes to add teacher-specific scopes
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Filter by current teacher's academy
        $teacherAcademy = static::getCurrentTeacherAcademy();
        if ($teacherAcademy) {
            $query->where('academy_id', $teacherAcademy->id);
        }
        
        return $query;
    }
    
    /**
     * Teacher resources should not allow creation when viewing all academies
     */
    public static function canCreate(): bool
    {
        if (static::isViewingAllAcademies()) {
            return false;
        }
        
        return static::isTeacher() && parent::canCreate();
    }
    
    /**
     * Get form schema with teacher-specific modifications
     * Child classes can override to restrict certain fields
     */
    protected static function getFormSchema(): array
    {
        return [];
    }
    
    /**
     * Apply teacher-specific form modifications
     * This method can be called by child classes to modify form behavior
     */
    protected static function modifyFormForTeachers(array $schema): array
    {
        // Add academy context information
        $teacherAcademy = static::getCurrentTeacherAcademy();
        if ($teacherAcademy) {
            // Add hidden academy_id field if not present
            $academyField = \Filament\Forms\Components\Hidden::make('academy_id')
                ->default($teacherAcademy->id);
            
            array_unshift($schema, $academyField);
        }
        
        return $schema;
    }
    
    /**
     * Get teacher-specific table columns
     * Child classes can override to customize table display
     */
    protected static function getTeacherTableColumns(): array
    {
        return [];
    }
    
    /**
     * Get academy relationship path for teacher resources
     * Override in child classes if academy relationship is different
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }
    
    /**
     * Check if resource supports bulk actions for teachers
     * Override in child classes to disable bulk actions if needed
     */
    protected static function supportsBulkActions(): bool
    {
        return true;
    }
    
    /**
     * Get navigation sort order for teacher resources
     * Default sort, child classes can override
     */
    protected static function getDefaultNavigationSort(): int
    {
        return 1;
    }
}
