<?php

namespace App\Filament\Supervisor\Resources;

use App\Filament\Shared\Resources\BaseStudentSessionReportResource;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionReportsResource\Pages\ListMonitoredQuranSessionReports;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionReportsResource\Pages\ViewMonitoredQuranSessionReport;
use App\Models\StudentSessionReport;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Monitored Quran Session Reports Resource for Supervisor Panel
 * Shows Quran session reports for the supervisor's assigned Quran teachers.
 * Read-only: supervisors can monitor but not edit reports.
 */
class MonitoredQuranSessionReportsResource extends BaseStudentSessionReportResource
{
    protected static bool $isScopedToTenant = false;

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 1;

    // ========================================
    // Abstract Methods Implementation
    // ========================================

    protected static function scopeEloquentQuery(Builder $query): Builder
    {
        $teacherIds = BaseSupervisorResource::getAssignedQuranTeacherIds();
        if (empty($teacherIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('teacher_id', $teacherIds);
    }

    protected static function getSessionInfoFormSection(): Section
    {
        return Section::make('معلومات الجلسة')
            ->schema([
                TextInput::make('session_id')
                    ->label('رقم الجلسة')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('student_id')
                    ->label('رقم الطالب')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('teacher_id')
                    ->label('رقم المعلم')
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(3);
    }

    protected static function getTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('عرض'),
            ]),
        ];
    }

    protected static function getTableBulkActions(): array
    {
        return [];
    }

    // ========================================
    // Authorization
    // ========================================

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

    public static function shouldRegisterNavigation(): bool
    {
        return BaseSupervisorResource::hasAssignedQuranTeachers();
    }

    // ========================================
    // Eloquent Query - bypass Filament tenant scoping
    // ========================================

    public static function getEloquentQuery(): Builder
    {
        $query = StudentSessionReport::query()->with(['session', 'student', 'teacher']);

        return static::scopeEloquentQuery($query);
    }

    // ========================================
    // Table Filters Override - limit teacher filter to assigned teachers
    // ========================================

    protected static function getTableFilters(): array
    {
        return [
            ...parent::getTableFilters(),

            SelectFilter::make('teacher_id')
                ->label('المعلم')
                ->options(fn () => User::whereIn('id', BaseSupervisorResource::getAssignedQuranTeacherIds())
                    ->get()
                    ->mapWithKeys(fn ($u) => [$u->id => $u->name ?? $u->email])
                )
                ->searchable(),
        ];
    }

    // ========================================
    // Pages
    // ========================================

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoredQuranSessionReports::route('/'),
            'view' => ViewMonitoredQuranSessionReport::route('/{record}'),
        ];
    }
}
