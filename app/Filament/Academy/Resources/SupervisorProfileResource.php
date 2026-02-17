<?php

namespace App\Filament\Academy\Resources;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\ListSupervisorProfiles;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\CreateSupervisorProfile;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\ViewSupervisorProfile;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\EditSupervisorProfile;
use App\Enums\UserType;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages;
use App\Filament\Shared\Resources\Profiles\BaseSupervisorProfileResource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SupervisorProfileResource extends BaseSupervisorProfileResource
{
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $academyId = Auth::user()->academy_id;
        return $query->where('academy_id', $academyId);
    }

    protected static function getTableActions(): array
    {
        return [
            ViewAction::make(),
            EditAction::make(),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [];
    }

    protected static function getTableColumns(): array
    {
        return array_merge(
            [ImageColumn::make('avatar')
                ->label('الصورة')
                ->circular()
                ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=9333ea&color=fff')],
            parent::getTableColumns()
        );
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(UserType::ADMIN->value) && auth()->user()?->academy_id !== null;
    }

    public static function canDelete($record): bool
    {
        return false;
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
