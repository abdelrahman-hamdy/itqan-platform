<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Shared\Resources\Profiles\BaseAcademicTeacherProfileResource;
use App\Filament\Supervisor\Resources\ManagedAcademicTeachersResource\Pages\ListManagedAcademicTeachers;
use App\Filament\Supervisor\Resources\ManagedAcademicTeachersResource\Pages\ViewManagedAcademicTeacher;
use App\Models\AcademicTeacherProfile;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Managed Academic Teachers Resource for Supervisor Panel
 *
 * Extends the shared base resource but scopes to assigned academic teachers only.
 * Read-only with activate/deactivate actions.
 */
class ManagedAcademicTeachersResource extends BaseAcademicTeacherProfileResource
{
    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'المعلمون الأكاديميون';

    protected static ?string $modelLabel = 'معلم أكاديمي';

    protected static ?string $pluralModelLabel = 'المعلمون الأكاديميون';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المعلمين';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return BaseSupervisorResource::canManageTeachers()
            && BaseSupervisorResource::hasAssignedAcademicTeachers();
    }

    /**
     * Override completely to bypass parent chain Filament tenant scoping.
     * Scope to assigned academic teachers using user_id.
     */
    public static function getEloquentQuery(): Builder
    {
        $teacherUserIds = BaseSupervisorResource::getAssignedAcademicTeacherIds();

        $query = AcademicTeacherProfile::query()->with(['user']);

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
                    ->url(fn (AcademicTeacherProfile $record): string => ManagedTeacherEarningsResource::getUrl('index', [
                        'tableFilters[teacher][values][0]' => 'academic_'.$record->id,
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
            'index' => ListManagedAcademicTeachers::route('/'),
            'view' => ViewManagedAcademicTeacher::route('/{record}'),
        ];
    }
}
