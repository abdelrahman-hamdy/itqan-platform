<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupervisorProfileResource\Pages\CreateSupervisorProfile;
use App\Filament\Resources\SupervisorProfileResource\Pages\EditSupervisorProfile;
use App\Filament\Resources\SupervisorProfileResource\Pages\ListSupervisorProfiles;
use App\Filament\Resources\SupervisorProfileResource\Pages\ViewSupervisorProfile;
use App\Filament\Shared\Resources\Profiles\BaseSupervisorProfileResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupervisorProfileResource extends BaseSupervisorProfileResource
{
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy'; // SupervisorProfile -> Academy (direct relationship)
    }

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                Action::make('activate')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->user?->update(['active_status' => true]))
                    ->visible(fn ($record) => $record->user && ! $record->user->active_status),
                Action::make('deactivate')
                    ->label('إيقاف')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->user?->update(['active_status' => false]))
                    ->visible(fn ($record) => $record->user && $record->user->active_status),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label(__('filament.actions.restore')),
                ForceDeleteAction::make()->label(__('filament.actions.force_delete')),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('activate')
                    ->label('تفعيل المحددين')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => true]))),
                BulkAction::make('deactivate')
                    ->label('إيقاف المحددين')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(fn ($record) => $record->user?->update(['active_status' => false]))),
                DeleteBulkAction::make()->label('حذف المحدد'),
                RestoreBulkAction::make()->label(__('filament.actions.restore_selected')),
                ForceDeleteBulkAction::make()->label(__('filament.actions.force_delete_selected')),
            ]),
        ];
    }

    protected static function getTableColumns(): array
    {
        return array_merge(
            [static::getAcademyColumn()],
            [ImageColumn::make('avatar')
                ->label('الصورة')
                ->circular()
                ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=9333ea&color=fff')],
            parent::getTableColumns()
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupervisorProfiles::route('/'),
            'create' => CreateSupervisorProfile::route('/create'),
            'view' => ViewSupervisorProfile::route('/{record}'),
            'edit' => EditSupervisorProfile::route('/{record}/edit'),
        ];
    }
}
