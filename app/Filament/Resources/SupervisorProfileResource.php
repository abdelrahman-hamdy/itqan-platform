<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupervisorProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseSupervisorProfileResource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
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
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make(),
            Tables\Actions\ForceDeleteAction::make(),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ]),
        ];
    }

    protected static function getTableColumns(): array
    {
        return array_merge(
            [static::getAcademyColumn()],
            [Tables\Columns\ImageColumn::make('avatar')
                ->label('الصورة')
                ->circular()
                ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=9333ea&color=fff')],
            parent::getTableColumns()
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupervisorProfiles::route('/'),
            'create' => Pages\CreateSupervisorProfile::route('/create'),
            'view' => Pages\ViewSupervisorProfile::route('/{record}'),
            'edit' => Pages\EditSupervisorProfile::route('/{record}/edit'),
        ];
    }
}
