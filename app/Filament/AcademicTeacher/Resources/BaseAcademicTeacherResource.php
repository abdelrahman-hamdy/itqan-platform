<?php

namespace App\Filament\AcademicTeacher\Resources;

use App\Filament\Shared\BaseTeacherResource as SharedBaseTeacherResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Resource for Academic Teacher Panel
 * Extends shared teacher resource with Academic-specific functionality
 */
abstract class BaseAcademicTeacherResource extends SharedBaseTeacherResource
{
    /**
     * Override to add Academic teacher specific query scoping
     * This method adds additional filtering specific to Academic teachers
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Additional Academic teacher specific filtering can be added here
        // For example, filtering by academic_teacher_id if applicable

        return $query;
    }

    /**
     * Get current Academic teacher's profile
     */
    protected static function getCurrentAcademicTeacherProfile(): ?\App\Models\AcademicTeacherProfile
    {
        $user = auth()->user();

        if (!$user || !$user->academicTeacherProfile) {
            return null;
        }

        return $user->academicTeacherProfile;
    }

    /**
     * Check if resource belongs to current Academic teacher
     * Override in child classes for resource-specific ownership logic
     */
    public static function canView(Model $record): bool
    {
        if (!static::isAcademicTeacher()) {
            return false;
        }

        // Allow parent's default check (academy-based)
        return parent::canView($record);
    }

    /**
     * Check if resource can be edited by current Academic teacher
     * Override in child classes for resource-specific edit permissions
     */
    public static function canEdit(Model $record): bool
    {
        if (!static::isAcademicTeacher()) {
            return false;
        }

        // Allow parent's default check (academy-based)
        return parent::canEdit($record);
    }
}
