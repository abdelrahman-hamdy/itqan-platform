<?php

namespace App\Filament\Resources;

abstract class BaseSettingsResource extends BaseResource
{
    /**
     * Settings resources should only be visible when specific academy is selected
     */
    protected static function isSettingsResource(): bool
    {
        return true;
    }

    /**
     * Settings resources should not show academy column even when viewing all academies
     * since they won't be visible anyway
     */
    protected static function shouldShowAcademyColumn(): bool
    {
        return false;
    }

    /**
     * Settings resources should not be accessible when viewing all academies
     */
    public static function canAccess(): bool
    {
        if (static::isViewingAllAcademies()) {
            return false;
        }

        return parent::canAccess();
    }

    /**
     * Prevent any CRUD operations when viewing all academies
     */
    public static function canCreate(): bool
    {
        if (static::isViewingAllAcademies()) {
            return false;
        }

        return parent::canCreate();
    }

    public static function canEdit($record): bool
    {
        if (static::isViewingAllAcademies()) {
            return false;
        }

        return parent::canEdit($record);
    }

    public static function canDelete($record): bool
    {
        if (static::isViewingAllAcademies()) {
            return false;
        }

        return parent::canDelete($record);
    }
}
