<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\CreateSupervisorProfile;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\EditSupervisorProfile;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\ListSupervisorProfiles;
use App\Filament\Academy\Resources\SupervisorProfileResource\Pages\ViewSupervisorProfile;
use App\Filament\Shared\Resources\Profiles\BaseSupervisorProfileResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
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
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                DeleteAction::make()
                    ->label('حذف'),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }

    protected static function getTableColumns(): array
    {
        return array_merge(
            [ImageColumn::make('avatar')
                ->label('الصورة')
                ->circular()
                ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=9333ea&color=fff')
                ->toggleable()],
            parent::getTableColumns()
        );
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(UserType::ADMIN->value) && auth()->user()?->academy_id !== null;
    }

    public static function canDelete($record): bool
    {
        return true;
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
