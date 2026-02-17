<?php

namespace App\Filament\Concerns;

use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Trait to add soft delete handling to Filament resources.
 *
 * Use this trait in resources where the model uses SoftDeletes.
 *
 * Usage:
 * 1. Add `use HasSoftDeletes;` to your resource class
 * 2. Call `static::getSoftDeletesFilter()` in your filters array
 * 3. Call `static::getSoftDeletesTableActions()` in your actions array (spread it)
 * 4. Call `static::getSoftDeletesBulkActions()` in your bulk actions array (spread it)
 * 5. Call `static::applySoftDeletesToQuery($query)` in getEloquentQuery()
 */
trait HasSoftDeletes
{
    /**
     * Get the TrashedFilter for the table
     */
    protected static function getSoftDeletesFilter(): TrashedFilter
    {
        return TrashedFilter::make()
            ->label(__('filament.filters.trashed'));
    }

    /**
     * Get restore and force delete actions for the table
     */
    protected static function getSoftDeletesTableActions(): array
    {
        return [
            RestoreAction::make()
                ->label(__('filament.actions.restore')),
            ForceDeleteAction::make()
                ->label(__('filament.actions.force_delete')),
        ];
    }

    /**
     * Get restore and force delete bulk actions
     */
    protected static function getSoftDeletesBulkActions(): array
    {
        return [
            RestoreBulkAction::make()
                ->label(__('filament.actions.restore_selected')),
            ForceDeleteBulkAction::make()
                ->label(__('filament.actions.force_delete_selected')),
        ];
    }

    /**
     * Apply soft deletes scope to query
     */
    protected static function applySoftDeletesToQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
