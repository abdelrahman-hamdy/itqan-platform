<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\Resources\BaseResource as SuperAdminBaseResource;
use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseAcademicTeacherResource extends SuperAdminBaseResource
{
    /**
     * Determine if this resource should be visible in navigation
     * Academic teacher resources are always visible within academic teacher dashboard
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    /**
     * Check if current user is an academic teacher
     */
    protected static function isAcademicTeacher(): bool
    {
        $user = auth()->user();
        return $user && $user->isAcademicTeacher();
    }
    
    /**
     * Get current academic teacher's academy
     */
    protected static function getCurrentAcademy(): ?\App\Models\Academy
    {
        $academyContextService = app(AcademyContextService::class);
        return $academyContextService->getCurrentAcademy();
    }
    
    /**
     * Get current academic teacher's ID
     */
    protected static function getCurrentTeacherId(): ?int
    {
        return auth()->id();
    }
    
    /**
     * Check if record belongs to current academic teacher
     * Override in child classes for specific ownership logic
     */
    public static function canView(Model $record): bool
    {
        if (!static::isAcademicTeacher()) {
            return false;
        }
        
        // Default: allow viewing if record belongs to academic teacher's academy
        $teacherAcademy = static::getCurrentAcademy();
        if ($teacherAcademy && $record->academy_id === $teacherAcademy->id) {
            return true;
        }
        
        return parent::canView($record);
    }
    
    /**
     * Check if record can be edited by current academic teacher
     * Override in child classes for specific edit permissions
     */
    public static function canEdit(Model $record): bool
    {
        if (!static::isAcademicTeacher()) {
            return false;
        }
        
        // Default: allow editing if record belongs to academic teacher's academy
        $teacherAcademy = static::getCurrentAcademy();
        if ($teacherAcademy && $record->academy_id === $teacherAcademy->id) {
            return true;
        }
        
        return parent::canEdit($record);
    }
    
    /**
     * Get the Eloquent query with academic teacher-specific filtering
     * Override in child classes to add academic teacher-specific scopes
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Filter by current academic teacher's academy
        $teacherAcademy = static::getCurrentAcademy();
        if ($teacherAcademy) {
            $query->where('academy_id', $teacherAcademy->id);
        }
        
        return $query;
    }
    
    /**
     * Academic teacher resources should not allow creation when viewing all academies
     */
    public static function canCreate(): bool
    {
        if (static::isViewingAllAcademies()) {
            return false;
        }
        
        return static::isAcademicTeacher() && parent::canCreate();
    }
    
    /**
     * Get form schema with academic teacher-specific modifications
     * Child classes can override to restrict certain fields
     */
    protected static function getFormSchema(): array
    {
        return [];
    }
    
    /**
     * Apply academic teacher-specific form modifications
     * This method can be called by child classes to modify form behavior
     */
    protected static function modifyFormForAcademicTeachers(array $schema): array
    {
        // Add academy context information
        $teacherAcademy = static::getCurrentAcademy();
        if ($teacherAcademy) {
            // Add hidden academy_id field if not present
            $academyField = \Filament\Forms\Components\Hidden::make('academy_id')
                ->default($teacherAcademy->id);
            
            array_unshift($schema, $academyField);
        }
        
        return $schema;
    }
    
    /**
     * Get academic teacher-specific table columns
     * Child classes can override to customize table display
     */
    protected static function getAcademicTeacherTableColumns(): array
    {
        return [];
    }
    
    /**
     * Get academy relationship path for academic teacher resources
     * Override in child classes if academy relationship is different
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }
    
    /**
     * Check if resource supports bulk actions for academic teachers
     * Override in child classes to disable bulk actions if needed
     */
    protected static function supportsBulkActions(): bool
    {
        return true;
    }
    
    /**
     * Get navigation sort order for academic teacher resources
     * Default sort, child classes can override
     */
    protected static function getDefaultNavigationSort(): int
    {
        return 1;
    }
    
    /**
     * Get subjects available to current academic teacher
     * Override in child classes if different logic is needed
     */
    protected static function getAvailableSubjects(): array
    {
        $academy = static::getCurrentAcademy();
        if (!$academy) {
            return [];
        }
        
        return \App\Models\Subject::where('academy_id', $academy->id)
            ->pluck('name', 'id')
            ->toArray();
    }
    
    /**
     * Get grade levels available to current academic teacher
     * Override in child classes if different logic is needed
     */
    protected static function getAvailableGradeLevels(): array
    {
        $academy = static::getCurrentAcademy();
        if (!$academy) {
            return [];
        }
        
        return \App\Models\AcademicGradeLevel::where('academy_id', $academy->id)
            ->pluck('name', 'id')
            ->toArray();
    }
}
