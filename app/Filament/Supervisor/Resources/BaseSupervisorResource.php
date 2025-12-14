<?php

namespace App\Filament\Supervisor\Resources;

use App\Models\SupervisorProfile;
use App\Services\AcademyContextService;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Resource for Supervisor Panel
 * Provides common functionality for all supervisor resources
 */
abstract class BaseSupervisorResource extends Resource
{
    /**
     * Disable automatic tenant scoping - we filter by supervisor in getEloquentQuery()
     */
    protected static bool $isScopedToTenant = false;

    /**
     * Check if current user is a supervisor
     */
    protected static function isSupervisor(): bool
    {
        $user = auth()->user();
        return $user && $user->isSupervisor();
    }

    /**
     * Get current supervisor's profile
     */
    protected static function getCurrentSupervisorProfile(): ?SupervisorProfile
    {
        $user = auth()->user();

        if (!$user || !$user->supervisorProfile) {
            return null;
        }

        return $user->supervisorProfile;
    }

    /**
     * Get current supervisor's academy
     */
    protected static function getCurrentSupervisorAcademy(): ?\App\Models\Academy
    {
        $academyContextService = app(AcademyContextService::class);
        return $academyContextService->getCurrentAcademy();
    }

    /**
     * Get assigned teacher IDs for current supervisor
     */
    protected static function getAssignedTeacherIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->assigned_teachers ?? [];
    }

    /**
     * Check if supervisor can access a department
     */
    protected static function canAccessDepartment(string $department): bool
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->canAccessDepartment($department) ?? false;
    }

    /**
     * Check if supervisor can monitor a specific teacher
     */
    protected static function canMonitorTeacher(int $teacherId): bool
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->canMonitorTeacher($teacherId) ?? false;
    }

    /**
     * Base query filtering - always scope to current academy
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $academy = static::getCurrentSupervisorAcademy();
        if ($academy) {
            $query->where('academy_id', $academy->id);
        }

        return $query;
    }

    /**
     * Supervisors typically should not create records
     * Override in child classes if creation is needed
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Supervisors typically should not delete records
     * Override in child classes if deletion is needed
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Check if supervisor can view a record
     */
    public static function canView(Model $record): bool
    {
        if (!static::isSupervisor()) {
            return false;
        }

        $academy = static::getCurrentSupervisorAcademy();
        if ($academy && isset($record->academy_id) && $record->academy_id !== $academy->id) {
            return false;
        }

        return true;
    }

    /**
     * Check if supervisor can edit a record
     * Default: supervisors can edit records they can view
     * Override for more restrictive permissions
     */
    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    /**
     * Get academy column (hidden since supervisors see only their academy)
     */
    protected static function getAcademyColumn(): \Filament\Tables\Columns\TextColumn
    {
        return \Filament\Tables\Columns\TextColumn::make('academy.name')
            ->label('الأكاديمية')
            ->sortable()
            ->searchable()
            ->visible(false);
    }

    /**
     * Get timezone for datetime displays
     */
    protected static function getTimezone(): string
    {
        return AcademyContextService::getTimezone();
    }
}
