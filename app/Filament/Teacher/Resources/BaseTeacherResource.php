<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Shared\BaseTeacherResource as SharedBaseTeacherResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base Resource for Quran Teacher Panel
 * Extends shared teacher resource with Quran-specific functionality
 */
abstract class BaseTeacherResource extends SharedBaseTeacherResource
{
    /**
     * Override to add Quran teacher specific query scoping
     * This method adds additional filtering specific to Quran teachers
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Additional Quran teacher specific filtering can be added here
        // For example, filtering by quran_teacher_id if applicable

        return $query;
    }

    /**
     * Get current Quran teacher's profile
     */
    protected static function getCurrentQuranTeacherProfile(): ?\App\Models\QuranTeacherProfile
    {
        $user = auth()->user();

        if (!$user || !$user->quranTeacherProfile) {
            return null;
        }

        return $user->quranTeacherProfile;
    }

    /**
     * Check if resource belongs to current Quran teacher
     * Override in child classes for resource-specific ownership logic
     */
    public static function canView(Model $record): bool
    {
        if (!static::isQuranTeacher()) {
            return false;
        }

        // Allow parent's default check (academy-based)
        return parent::canView($record);
    }

    /**
     * Check if resource can be edited by current Quran teacher
     * Override in child classes for resource-specific edit permissions
     */
    public static function canEdit(Model $record): bool
    {
        if (!static::isQuranTeacher()) {
            return false;
        }

        // Allow parent's default check (academy-based)
        return parent::canEdit($record);
    }
}
