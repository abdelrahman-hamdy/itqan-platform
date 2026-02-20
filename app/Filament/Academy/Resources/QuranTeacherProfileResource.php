<?php

namespace App\Filament\Academy\Resources;

use App\Enums\UserType;
use App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages\CreateQuranTeacherProfile;
use App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages\EditQuranTeacherProfile;
use App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages\ListQuranTeacherProfiles;
use App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages\ViewQuranTeacherProfile;
use App\Filament\Shared\Resources\Profiles\BaseQuranTeacherProfileResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuranTeacherProfileResource extends BaseQuranTeacherProfileResource
{
    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query->where('academy_id', Auth::user()->academy_id);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                EditAction::make()
                    ->label('تعديل'),
                Action::make('toggle_active')
                    ->label(fn ($record) => $record->user?->active_status ? 'تعطيل الحساب' : 'تفعيل الحساب')
                    ->icon(fn ($record) => $record->user?->active_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->user?->active_status ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->user?->update([
                            'active_status' => ! $record->user->active_status,
                        ]);
                    }),
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
                ->defaultImageUrl(fn ($record) => config('services.ui_avatars.base_url').'?name='.urlencode($record->full_name ?? 'N/A').'&background=059669&color=fff')
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
            'index' => ListQuranTeacherProfiles::route('/'),
            'create' => CreateQuranTeacherProfile::route('/create'),
            'view' => ViewQuranTeacherProfile::route('/{record}'),
            'edit' => EditQuranTeacherProfile::route('/{record}/edit'),
        ];
    }
}
