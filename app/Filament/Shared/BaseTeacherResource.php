<?php

namespace App\Filament\Shared;

use App\Filament\Resources\BaseResource as SuperAdminBaseResource;
use App\Services\AcademyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Unified Base Resource for All Teacher Panels
 * Provides common functionality for both Quran and Academic teachers
 */
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
     * Check if current user is a teacher (Quran or Academic)
     */
    protected static function isTeacher(): bool
    {
        $user = auth()->user();

        return $user && ($user->isQuranTeacher() || $user->isAcademicTeacher());
    }

    /**
     * Check if current user is a Quran teacher specifically
     */
    protected static function isQuranTeacher(): bool
    {
        $user = auth()->user();

        return $user && $user->isQuranTeacher();
    }

    /**
     * Check if current user is an Academic teacher specifically
     */
    protected static function isAcademicTeacher(): bool
    {
        $user = auth()->user();

        return $user && $user->isAcademicTeacher();
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
     * Get current teacher's profile (Quran or Academic)
     * Override in child classes for specific teacher type
     */
    protected static function getCurrentTeacherProfile(): ?Model
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        // Try Quran teacher first
        if ($user->quranTeacherProfile) {
            return $user->quranTeacherProfile;
        }

        // Then Academic teacher
        if ($user->academicTeacherProfile) {
            return $user->academicTeacherProfile;
        }

        return null;
    }

    /**
     * Check if record belongs to current teacher
     * Default implementation checks academy_id
     * Override in child classes for specific ownership logic
     */
    public static function canView(Model $record): bool
    {
        if (! static::isTeacher()) {
            return false;
        }

        // Default: allow viewing if record belongs to teacher's academy
        $teacherAcademy = static::getCurrentTeacherAcademy();
        if ($teacherAcademy && isset($record->academy_id) && $record->academy_id === $teacherAcademy->id) {
            return true;
        }

        return parent::canView($record);
    }

    /**
     * Check if record can be edited by current teacher
     * Default implementation checks academy_id
     * Override in child classes for specific edit permissions
     */
    public static function canEdit(Model $record): bool
    {
        if (! static::isTeacher()) {
            return false;
        }

        // Default: allow editing if record belongs to teacher's academy
        $teacherAcademy = static::getCurrentTeacherAcademy();
        if ($teacherAcademy && isset($record->academy_id) && $record->academy_id === $teacherAcademy->id) {
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
     * Check if record can be deleted by current teacher
     * Default: allow deletion if can edit
     * Override for more restrictive deletion policies
     */
    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
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

    /**
     * Hide academy column in tables since teachers only see their academy
     * Override getAcademyColumn from BaseResource
     */
    protected static function getAcademyColumn(): \Filament\Tables\Columns\TextColumn
    {
        return \Filament\Tables\Columns\TextColumn::make('academy.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(false) // Always hide for teachers
            ->placeholder('غير محدد');
    }

    /**
     * Get subjects available to current teacher
     * For academic teachers
     */
    protected static function getAvailableSubjects(): array
    {
        $academy = static::getCurrentTeacherAcademy();
        if (! $academy) {
            return [];
        }

        return \App\Models\AcademicSubject::where('academy_id', $academy->id)
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get grade levels available to current teacher
     * For academic teachers
     */
    protected static function getAvailableGradeLevels(): array
    {
        $academy = static::getCurrentTeacherAcademy();
        if (! $academy) {
            return [];
        }

        return \App\Models\AcademicGradeLevel::where('academy_id', $academy->id)
            ->pluck('name', 'id')
            ->toArray();
    }
}
