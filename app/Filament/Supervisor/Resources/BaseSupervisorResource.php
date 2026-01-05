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
     * Get resource IDs that supervisor is responsible for (by model class).
     * Used for interactive courses (direct assignment).
     */
    protected static function getResponsibleResourceIds(string $modelClass): array
    {
        $profile = static::getCurrentSupervisorProfile();
        if (!$profile) {
            return [];
        }

        return $profile->getResponsibilityIds($modelClass);
    }

    /**
     * Check if supervisor has any responsibilities of a specific type.
     */
    protected static function hasAnyResponsibilities(string $modelClass): bool
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->hasResponsibilityType($modelClass) ?? false;
    }

    /**
     * Check if supervisor has any responsibilities at all.
     */
    protected static function hasResponsibilities(): bool
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->hasAnyResponsibilities() ?? false;
    }

    // ========================================
    // Teacher-based supervision methods
    // ========================================

    /**
     * Get assigned Quran teacher IDs.
     */
    protected static function getAssignedQuranTeacherIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->getAssignedQuranTeacherIds() ?? [];
    }

    /**
     * Get assigned Academic teacher IDs.
     */
    protected static function getAssignedAcademicTeacherIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->getAssignedAcademicTeacherIds() ?? [];
    }

    /**
     * Get all assigned teacher IDs (both Quran and Academic).
     */
    protected static function getAllAssignedTeacherIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->getAllAssignedTeacherIds() ?? [];
    }

    /**
     * Check if supervisor has any assigned Quran teachers.
     */
    protected static function hasAssignedQuranTeachers(): bool
    {
        return !empty(static::getAssignedQuranTeacherIds());
    }

    /**
     * Check if supervisor has any assigned Academic teachers.
     */
    protected static function hasAssignedAcademicTeachers(): bool
    {
        return !empty(static::getAssignedAcademicTeacherIds());
    }

    /**
     * Check if supervisor has any assigned teachers.
     */
    protected static function hasAssignedTeachers(): bool
    {
        return static::hasAssignedQuranTeachers() || static::hasAssignedAcademicTeachers();
    }

    /**
     * Check if supervisor can manage teacher profiles, earnings, payouts.
     */
    protected static function canManageTeachers(): bool
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->canManageTeachers() ?? false;
    }

    /**
     * Get academic teacher profile IDs from assigned academic teacher user IDs.
     * Used for filtering resources that reference AcademicTeacherProfile.
     */
    protected static function getAssignedAcademicTeacherProfileIds(): array
    {
        $userIds = static::getAssignedAcademicTeacherIds();
        if (empty($userIds)) {
            return [];
        }

        return \App\Models\AcademicTeacherProfile::whereIn('user_id', $userIds)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get derived interactive course IDs from assigned academic teachers.
     */
    protected static function getDerivedInteractiveCourseIds(): array
    {
        $profile = static::getCurrentSupervisorProfile();
        return $profile?->getDerivedInteractiveCourseIds() ?? [];
    }

    /**
     * Check if supervisor has any derived interactive courses.
     */
    protected static function hasDerivedInteractiveCourses(): bool
    {
        return !empty(static::getDerivedInteractiveCourseIds());
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
     * Supervisors can create records for their assigned teachers
     * Child classes may override to add additional checks
     */
    public static function canCreate(): bool
    {
        if (!static::isSupervisor()) {
            return false;
        }

        // At least one assigned teacher is required to create sessions
        return static::hasAssignedTeachers();
    }

    /**
     * Supervisors can delete records for their assigned teachers
     */
    public static function canDelete(Model $record): bool
    {
        return static::canManageRecord($record);
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
     * Supervisors can edit records belonging to their assigned teachers
     */
    public static function canEdit(Model $record): bool
    {
        return static::canManageRecord($record);
    }

    /**
     * Check if supervisor can manage (edit/delete) a specific record.
     * Verifies the record belongs to one of the supervisor's assigned teachers.
     */
    protected static function canManageRecord(Model $record): bool
    {
        // Must be a supervisor
        if (!static::isSupervisor()) {
            return false;
        }

        // Must be in the same academy
        $academy = static::getCurrentSupervisorAcademy();
        if ($academy && isset($record->academy_id) && $record->academy_id !== $academy->id) {
            return false;
        }

        // Check if record's teacher is assigned to this supervisor
        return static::isRecordTeacherAssigned($record);
    }

    /**
     * Check if the record's teacher is assigned to this supervisor.
     * Handles different teacher field names across models.
     */
    protected static function isRecordTeacherAssigned(Model $record): bool
    {
        $quranTeacherIds = static::getAssignedQuranTeacherIds();
        $academicTeacherIds = static::getAssignedAcademicTeacherIds();

        // Check Quran teacher ID (on QuranSession, QuranCircle, QuranIndividualCircle)
        if (isset($record->quran_teacher_id) && in_array($record->quran_teacher_id, $quranTeacherIds)) {
            return true;
        }

        // Check Academic teacher ID (on AcademicSession, AcademicIndividualLesson)
        if (isset($record->academic_teacher_id)) {
            // academic_teacher_id might be a profile ID, need to check user_id
            $academicProfileIds = static::getAssignedAcademicTeacherProfileIds();
            if (in_array($record->academic_teacher_id, $academicProfileIds)) {
                return true;
            }
        }

        // Check via teacher relationship (for resources with teacher relation)
        if (method_exists($record, 'teacher') && $record->teacher) {
            $teacherId = $record->teacher->id ?? $record->teacher->user_id ?? null;
            if ($teacherId && (in_array($teacherId, $quranTeacherIds) || in_array($teacherId, $academicTeacherIds))) {
                return true;
            }
        }

        // Check via quranTeacher relationship
        if (method_exists($record, 'quranTeacher') && $record->quranTeacher) {
            $teacherId = $record->quranTeacher->id ?? null;
            if ($teacherId && in_array($teacherId, $quranTeacherIds)) {
                return true;
            }
        }

        // Check via academicTeacher relationship
        if (method_exists($record, 'academicTeacher') && $record->academicTeacher) {
            $profileId = $record->academicTeacher->id ?? null;
            $academicProfileIds = static::getAssignedAcademicTeacherProfileIds();
            if ($profileId && in_array($profileId, $academicProfileIds)) {
                return true;
            }
        }

        // Check for InteractiveCourse via course relationship and assigned_teacher_id
        if (method_exists($record, 'course') && $record->course) {
            $courseTeacherId = $record->course->assigned_teacher_id ?? null;
            $academicProfileIds = static::getAssignedAcademicTeacherProfileIds();
            if ($courseTeacherId && in_array($courseTeacherId, $academicProfileIds)) {
                return true;
            }
        }

        // For InteractiveCourse model directly
        if (isset($record->assigned_teacher_id)) {
            $academicProfileIds = static::getAssignedAcademicTeacherProfileIds();
            if (in_array($record->assigned_teacher_id, $academicProfileIds)) {
                return true;
            }
        }

        // Default: no teacher match found
        return false;
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
