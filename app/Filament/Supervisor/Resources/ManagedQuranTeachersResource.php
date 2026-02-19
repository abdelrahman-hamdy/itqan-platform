<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Shared\Resources\Profiles\BaseQuranTeacherProfileResource;
use App\Filament\Supervisor\Resources\ManagedQuranTeachersResource\Pages\ListManagedQuranTeachers;
use App\Filament\Supervisor\Resources\ManagedQuranTeachersResource\Pages\ViewManagedQuranTeacher;
use App\Models\QuranTeacherProfile;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Managed Quran Teachers Resource for Supervisor Panel
 *
 * Extends the shared base resource but scopes to assigned quran teachers only.
 * Read-only with activate/deactivate actions.
 */
class ManagedQuranTeachersResource extends BaseQuranTeacherProfileResource
{
    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'معلمو القرآن';

    protected static ?string $modelLabel = 'معلم قرآن';

    protected static ?string $pluralModelLabel = 'معلمو القرآن';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return BaseSupervisorResource::canManageTeachers()
            && BaseSupervisorResource::hasAssignedQuranTeachers();
    }

    /**
     * Override completely to bypass parent chain Filament tenant scoping.
     * Scope to assigned quran teachers using user_id.
     */
    public static function getEloquentQuery(): Builder
    {
        $teacherUserIds = BaseSupervisorResource::getAssignedQuranTeacherIds();

        $query = QuranTeacherProfile::query()->with(['user']);

        if (empty($teacherUserIds)) {
            return $query->whereRaw('1 = 0');
        }

        return static::scopeEloquentQuery($query->whereIn('user_id', $teacherUserIds));
    }

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        return $query;
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('عرض'),
                Action::make('view_earnings')
                    ->label('الأرباح')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn (QuranTeacherProfile $record): string => ManagedTeacherEarningsResource::getUrl('index', [
                        'tableFilters[teacher][values][0]' => 'quran_'.$record->id,
                    ]))
                    ->visible(fn () => BaseSupervisorResource::canManageTeachers()),
                Action::make('activate')
                    ->label('تفعيل المعلم')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->user?->update(['active_status' => true]))
                    ->visible(fn ($record) => $record->user && ! $record->user->active_status),
                Action::make('deactivate')
                    ->label('إيقاف المعلم')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->user?->update(['active_status' => false]))
                    ->visible(fn ($record) => $record->user && $record->user->active_status),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManagedQuranTeachers::route('/'),
            'view' => ViewManagedQuranTeacher::route('/{record}'),
        ];
    }
}
